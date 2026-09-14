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
use Illuminate\Support\Facades\DB;

/**
 * Fills the session that triggered an instructor attendance block, run once when
 * an admin approves that block (see docs/instructor-attendance-block-proposal.md,
 * "The triggering session"). A retroactive manual entry for this specific class
 * can't be trusted - the instructor who missed it is the one being asked, after
 * the fact, whether students were present - so the system fills it from last
 * week's record instead of letting the instructor re-track it.
 *
 * Only fills students with no attendance row yet for this date; anything already
 * tracked (shouldn't exist for a Case 1 complete no-show, but kept generic) is
 * left untouched. Ends the session in `auto_recorded`, the same state - and the
 * same audited OverrideAttendanceRecord correction path - as any other
 * system-generated attendance the instructor can't freely resubmit.
 */
class AutoFillTriggeringSessionFromLastWeek
{
    public function __construct(
        private readonly AbsenceBlockEvaluator $lockEvaluator,
        private readonly PermissionLimitEvaluator $permissionEvaluator,
        private readonly AutoBlockStudent $autoBlock,
    ) {}

    public function handle(int $sessionId, User $actor): void
    {
        DB::transaction(function () use ($sessionId, $actor): void {
            $session = ClassSession::query()->lockForUpdate()->find($sessionId);

            if (! $session || ! in_array($session->status, [
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
            ], true)) {
                return;
            }

            $class = StudyClass::query()->find($session->study_class_id);

            if (! $class) {
                return;
            }

            $enrollments = StudentEnrollment::query()
                ->where('study_class_id', $session->study_class_id)
                ->where('enrollment_status', 'active')
                ->get(['id', 'student_id']);

            if ($enrollments->isEmpty()) {
                return;
            }

            $attendanceDate = $session->session_date->toDateString();
            $lastWeekDate = Carbon::parse($attendanceDate)->subDays(7)->toDateString();

            $existingEnrollmentIds = DB::table('student_attendances')
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $attendanceDate)
                ->pluck('student_enrollment_id')
                ->all();

            $missingEnrollments = $enrollments->reject(
                fn (StudentEnrollment $enrollment): bool => in_array($enrollment->id, $existingEnrollmentIds, true)
            );

            if ($missingEnrollments->isEmpty()) {
                // Nothing left to fill - still resolve the session so it doesn't
                // stay stuck in pre_attendance/partial forever.
                $session->update([
                    'status' => ClassSession::STATUS_AUTO_RECORDED,
                    'recorded_at' => Carbon::now('Asia/Phnom_Penh'),
                ]);

                return;
            }

            $lastWeekRows = DB::table('student_attendances')
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $lastWeekDate)
                ->whereIn('student_enrollment_id', $missingEnrollments->pluck('id'))
                ->get()
                ->keyBy('student_enrollment_id');

            $now = Carbon::now('Asia/Phnom_Penh');
            $settledAbsent = [];

            foreach ($missingEnrollments as $enrollment) {
                $studentId = (int) $enrollment->student_id;
                $lastWeekRow = $lastWeekRows->get($enrollment->id);

                // No prior-week record (new enrollment, or last week was itself
                // unresolved) - conservative default, never fabricate a "present".
                $status = $lastWeekRow
                    ? StudentAttendance::labelForFlags($lastWeekRow)
                    : StudentAttendance::STATUS_ABSENT;
                $note = "Auto-filled from last week's attendance after the instructor's attendance block was approved.";

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

                DB::table('student_attendances')->updateOrInsert(
                    [
                        'study_class_id' => $session->study_class_id,
                        'student_enrollment_id' => $enrollment->id,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'student_id' => $studentId,
                        'tracked_by' => null,
                        ...StudentAttendance::flagsFor($status),
                        'locked' => $lock->locked,
                        'lock_reason' => $lock->locked ? $lock->reason : null,
                        'locked_block_id' => $lock->blockId,
                        'source' => StudentAttendance::SOURCE_AUTO,
                        'note' => $note,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );

                if ($status === StudentAttendance::STATUS_ABSENT) {
                    $settledAbsent[] = $studentId;
                }
            }

            $session->update([
                'status' => ClassSession::STATUS_AUTO_RECORDED,
                'recorded_at' => $now,
            ]);

            // Raise / escalate absence blocks for anyone who ended up absent, same
            // as every other attendance-writing path.
            foreach (array_unique($settledAbsent) as $absentStudentId) {
                $this->autoBlock->handle($absentStudentId, $session->study_class_id, $attendanceDate, $actor);
            }
        });
    }
}
