<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use App\Modules\AbsenceBlock\Services\AbsenceBlockEvaluator;
use App\Modules\AbsenceBlock\Services\PermissionLimitEvaluator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared per-session fill logic behind both admin unblock paths
 * (docs/instructor-attendance-block-approve-after-permission.md):
 *
 *  - AutoFillTriggeringSessionFromLastWeek (plain Approve) delegates its whole body
 *    here — one session, one transaction, the exact historic behavior.
 *  - BackfillAllMissedSessionsFromLastWeek (Approve After Permission) reuses the
 *    same computeRows()/upsertRows()/raiseAutoBlocks() building blocks in bulk.
 *
 * A retroactive manual entry for a missed class can't be trusted — the instructor who
 * missed it is the one being asked, after the fact, whether students were present — so
 * every stuck session is filled from last week's record instead of letting the
 * instructor re-track it.
 *
 * Only students with no attendance row yet for that date are filled; anything already
 * tracked is left untouched. Ended sessions land in `auto_recorded`, the same state —
 * and the same audited OverrideAttendanceRecord correction path — as any other
 * system-generated attendance.
 */
class FillSessionFromLastWeek
{
    public const NOTE = "Auto-filled from last week's attendance after the instructor's attendance block was approved.";

    private const UPSERT_CHUNK = 500;

    public function __construct(
        private readonly AbsenceBlockEvaluator $lockEvaluator,
        private readonly PermissionLimitEvaluator $permissionEvaluator,
        private readonly AutoBlockStudent $autoBlock,
    ) {}

    /**
     * Single-session entry point. Exactly mirrors the historic
     * AutoFillTriggeringSessionFromLastWeek behavior: unchanged session lock, one
     * transaction, raise absence blocks inside it. Loads and writes per session
     * (a handful of students), so no bulk batching is called for here.
     */
    public function handle(int $sessionId, User $actor): void
    {
        DB::transaction(function () use ($sessionId, $actor): void {
            $session = ClassSession::query()->lockForUpdate()->find($sessionId);

            if (! $this->isFillable($session)) {
                return;
            }

            $class = StudyClass::query()->find($session->study_class_id);

            if ($class === null) {
                return;
            }

            $enrollments = StudentEnrollment::query()
                ->where('study_class_id', $session->study_class_id)
                ->where('enrollment_status', 'active')
                ->get(['id', 'student_id']);

            if ($enrollments->isEmpty()) {
                return;
            }

            $now = Carbon::now('Asia/Phnom_Penh');
            $attendanceDate = $session->session_date->toDateString();
            $lastWeekDate = self::lastWeekDate($attendanceDate);

            $existingKeys = DB::table('student_attendances')
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $attendanceDate)
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->pluck('student_enrollment_id')
                ->flip();

            $lastWeekRows = DB::table('student_attendances')
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $lastWeekDate)
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->get()
                ->keyBy('student_enrollment_id');

            $result = $this->computeRows($session, $class, $enrollments, $existingKeys, $lastWeekRows, $now);

            $this->upsertRows($result['rows']);
            $this->resolveSession($session, $now);

            if ($result['rows'] !== []) {
                $this->raiseAutoBlocks($result['absent'], $actor);
            }
        });
    }

    public static function lastWeekDate(string $attendanceDate): string
    {
        return Carbon::parse($attendanceDate)->subDays(7)->toDateString();
    }

    public function isFillable(?ClassSession $session): bool
    {
        return $session !== null && in_array($session->status, [
            ClassSession::STATUS_PRE_ATTENDANCE,
            ClassSession::STATUS_PARTIAL,
        ], true);
    }

    /**
     * Pure per-session computation shared with the bulk path: given a session, its
     * active enrollments, and already-loaded lookups, decide each missing student's
     * row without issuing any queries. $existingKeys / $lastWeekRows are keyed by
     * student_enrollment_id (empty collections when a session has none).
     *
     * @return array{rows: list<array<string, mixed>>, absent: list<array{int, int, string}>}
     */
    public function computeRows(
        ClassSession $session,
        StudyClass $class,
        Collection $enrollments,
        Collection $existingKeys,
        Collection $lastWeekRows,
        Carbon $now,
    ): array {
        $attendanceDate = $session->session_date->toDateString();
        $rows = [];
        $absent = [];

        foreach ($enrollments as $enrollment) {
            if ($existingKeys->has($enrollment->id)) {
                continue;
            }

            $studentId = (int) $enrollment->student_id;
            $lastWeekRow = $lastWeekRows->get($enrollment->id);

            // No prior-week record (new enrollment, or last week was itself
            // unresolved) - conservative default, never fabricate a "present".
            $status = $lastWeekRow
                ? StudentAttendance::labelForFlags($lastWeekRow)
                : StudentAttendance::STATUS_ABSENT;
            $note = self::NOTE;

            // Same per-student rules every other attendance-writing path applies -
            // a locked student can't come out "present" just because last week did,
            // and a copied "permission" still has to clear the current quota.
            $lock = $this->lockEvaluator->evaluate($studentId, $session->study_class_id, $attendanceDate);

            if ($lock->locked) {
                $status = StudentAttendance::STATUS_ABSENT;
                $note = $lock->reason;
            } elseif ($status === StudentAttendance::STATUS_PERMISSION) {
                $permission = $this->permissionEvaluator->resolve($studentId, $class, $attendanceDate);
                $status = $permission['status'];
                $note = $permission['note'] ?? $note;
            }

            $rows[] = [
                'study_class_id' => $session->study_class_id,
                'student_enrollment_id' => $enrollment->id,
                'attendance_date' => $attendanceDate,
                'student_id' => $studentId,
                'tracked_by' => null,
                ...StudentAttendance::flagsFor($status),
                'locked' => $lock->locked,
                'lock_reason' => $lock->locked ? $lock->reason : null,
                'locked_block_id' => $lock->blockId,
                'source' => StudentAttendance::SOURCE_AUTO,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($status === StudentAttendance::STATUS_ABSENT) {
                $absent[] = [$studentId, (int) $session->study_class_id, $attendanceDate];
            }
        }

        return ['rows' => $rows, 'absent' => $absent];
    }

    /**
     * Chunked bulk upsert on the table's unique day key. Rows passed here never
     * collide with existing attendance (existing enrollments are excluded during
     * computation), so conflicts are a retry-run edge, not the normal path.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertRows(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
            DB::table('student_attendances')->upsert(
                $chunk,
                ['study_class_id', 'student_enrollment_id', 'attendance_date'],
                ['student_id', 'tracked_by', 'present', 'absent', 'permission', 'late', 'locked', 'lock_reason', 'locked_block_id', 'source', 'note', 'updated_at'],
            );
        }
    }

    public function resolveSession(ClassSession $session, Carbon $now): void
    {
        if ($session->status === ClassSession::STATUS_AUTO_RECORDED) {
            return;
        }

        $session->update([
            'status' => ClassSession::STATUS_AUTO_RECORDED,
            'recorded_at' => $now,
        ]);
    }

    /**
     * Raise / escalate absence blocks for anyone who ended up absent, same as every
     * other attendance-writing path. AutoBlockStudent is its own system with its own
     * correctness rules; it is intentionally left untouched (and is the dominant per
     * call cost — the caller decides whether to run it inside or outside the main
     * write transaction).
     *
     * @param  list<array{int, int, string}>  $absentTuples  [studentId, studyClassId, attendanceDate]
     */
    public function raiseAutoBlocks(array $absentTuples, User $actor): void
    {
        foreach (array_unique($absentTuples, SORT_REGULAR) as [$studentId, $studyClassId, $attendanceDate]) {
            $this->autoBlock->handle($studentId, $studyClassId, $attendanceDate, $actor);
        }
    }
}