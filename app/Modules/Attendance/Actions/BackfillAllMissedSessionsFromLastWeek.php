<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\InstructorAttendanceBlock;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Approve After Permission" — backfills EVERY ClassSession still stuck for an
 * instructor whose block is approved under a leave/permission explanation, not just
 * the one session that triggered the block (docs/instructor-attendance-block-approve-after-permission.md).
 *
 * Performance notes (read before touching):
 *
 *  - One batch-load pass up front: candidate sessions, their active enrollments, the
 *    attendance rows already tracked on those dates, and the last-week rows they will
 *    be copied from. Nothing is queried inside the per-session/per-student loop — the
 *    shared computation (FillSessionFromLastWeek::computeRows()) works purely off
 *    in-memory, keyed collections.
 *  - All writes go through one bulk DB::table()->upsert() per chunk instead of a
 *    per-row updateOrInsert loop.
 *  - Transactions are bounded: sessions are processed CHUNK_SESSIONS at a time (100),
 *    each chunk in its own transaction, so row-lock hold time and rollback blast
 *    radius stay small. The instructor is blocked, so no instructor write race exists
 *    on these stale pre_attendance/partial sessions, and the auto-record scheduler
 *    only acts on 'pending' rows — which is why session rows are not individually
 *    lockForUpdate'd here.
 *  - Absence-block raises (AutoBlockStudent) run AFTER each chunk's write transaction
 *    commits: they are a separate system with their own transactions/queries and are
 *    the dominant per-call cost. If one fails, the sweep has already succeeded and can
 *    simply be re-run (the chunked upsert and the pre_attendance/partial->auto_recorded
 *    flip are both idempotent).
 *  - Sync, not queued: QUEUE_CONNECTION=sync means a ShouldQueue job would execute
 *    inline in the same request anyway, there is no queued-job precedent in this app
 *    to build on, and a realistic backlog is small (a blocked instructor accumulates
 *    only a handful of stuck sessions per class during the block window). The
 *    chunked-transaction safeguards above exist precisely so the synchronous path
 *    stays bounded if that assumption ever breaks.
 */
class BackfillAllMissedSessionsFromLastWeek
{
    /** Sessions per commit batch — bounds lock hold time and rollback blast radius. */
    private const CHUNK_SESSIONS = 100;

    private const SQL_CHUNK = 500;

    public function __construct(
        private readonly FillSessionFromLastWeek $fill,
    ) {}

    public function handle(InstructorAttendanceBlock $block, User $actor): void
    {
        $sessions = $this->candidateSessions($block);

        if ($sessions->isEmpty()) {
            return;
        }

        $now = Carbon::now('Asia/Phnom_Penh');
        $classIds = $sessions->pluck('study_class_id')->map(static fn ($id): int => (int) $id)->unique()->values();

        $classes = $this->loadClasses($classIds);
        $enrollmentsByClass = $this->loadEnrollments($classIds);
        $existingByKey = $this->loadAttendanceRows($classIds, $sessions, false);
        $lastWeekByKey = $this->loadAttendanceRows($classIds, $sessions, true);

        foreach ($sessions->chunk(self::CHUNK_SESSIONS) as $chunk) {
            $this->processChunk($chunk, $classes, $enrollmentsByClass, $existingByKey, $lastWeekByKey, $now, $actor);
        }
    }

    private function candidateSessions(InstructorAttendanceBlock $block): Collection
    {
        return ClassSession::query()
            ->join('study_classes', 'study_classes.id', '=', 'class_sessions.study_class_id')
            ->where('study_classes.teacher_id', $block->instructor_id)
            ->whereIn('class_sessions.status', [
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
            ])
            ->whereDate('class_sessions.session_date', '<=', $block->blocked_at->toDateString())
            ->orderBy('class_sessions.id')
            ->select('class_sessions.*')
            ->get();
    }

    /** @param  Collection<int, int>  $classIds */
    private function loadClasses(Collection $classIds): Collection
    {
        $classes = new Collection();

        foreach ($classIds->chunk(self::SQL_CHUNK) as $chunk) {
            // Term is eager loaded so AbsenceRuleMatcher::isWeekendClass() can resolve
            // a class's days without a lazy-load query per permission check.
            $classes = $classes->concat(StudyClass::query()->with('term')->whereIn('id', $chunk)->get());
        }

        return $classes->keyBy('id');
    }

    /** @return Collection<int, Collection<int, StudentEnrollment>>  keyed by study_class_id */
    private function loadEnrollments(Collection $classIds): Collection
    {
        $enrollments = new Collection();

        foreach ($classIds->chunk(self::SQL_CHUNK) as $chunk) {
            $enrollments = $enrollments->concat(
                StudentEnrollment::query()
                    ->whereIn('study_class_id', $chunk)
                    ->where('enrollment_status', 'active')
                    ->get(['id', 'study_class_id', 'student_id'])
            );
        }

        return $enrollments->groupBy('study_class_id');
    }

    /**
     * One batched query per class-id chunk for every relevant date (either the
     * session dates themselves or their last-week counterparts), returned keyed by
     * "study_class_id|date" => Collection<student_enrollment_id, row> so a session's
     * existing-attendance and last-week lookups are O(1) collections, not queries.
     */
    private function loadAttendanceRows(Collection $classIds, Collection $sessions, bool $lastWeek): Collection
    {
        $dates = $sessions
            ->map(static fn (ClassSession $session): string => $lastWeek
                ? FillSessionFromLastWeek::lastWeekDate($session->session_date->toDateString())
                : $session->session_date->toDateString())
            ->unique()
            ->values();

        $byKey = new Collection();

        foreach ($classIds->chunk(self::SQL_CHUNK) as $chunk) {
            $rows = DB::table('student_attendances')
                ->whereIn('study_class_id', $chunk)
                ->whereIn('attendance_date', $dates)
                ->select([
                    'study_class_id',
                    'attendance_date',
                    'student_enrollment_id',
                    'present',
                    'absent',
                    'permission',
                    'late',
                ])
                ->get();

            foreach ($rows as $row) {
                $key = $row->study_class_id.'|'.$row->attendance_date;
                $bucket = $byKey->get($key, new Collection());
                $bucket->put($row->student_enrollment_id, $row);
                $byKey->put($key, $bucket);
            }
        }

        return $byKey;
    }

    /**
     * Compute every row for a chunk of sessions off the pre-loaded lookups, write
     * them in one bulk upsert, flip the sessions to auto_recorded — all inside one
     * bounded transaction — then raise absence blocks outside it.
     */
    private function processChunk(
        Collection $chunk,
        Collection $classes,
        Collection $enrollmentsByClass,
        Collection $existingByKey,
        Collection $lastWeekByKey,
        Carbon $now,
        User $actor,
    ): void {
        [$absent] = DB::transaction(function () use ($chunk, $classes, $enrollmentsByClass, $existingByKey, $lastWeekByKey, $now): array {
            $rows = [];
            $absent = [];
            $resolvedSessions = [];

            foreach ($chunk as $session) {
                $class = $classes->get($session->study_class_id);

                if ($class === null) {
                    continue;
                }

                $enrollments = $enrollmentsByClass->get($session->study_class_id, new Collection());

                if ($enrollments->isEmpty()) {
                    continue;
                }

                $attendanceDate = $session->session_date->toDateString();
                $lastWeekDate = FillSessionFromLastWeek::lastWeekDate($attendanceDate);

                $existingKeys = $existingByKey->get($session->study_class_id.'|'.$attendanceDate, new Collection());
                $lastWeekRows = $lastWeekByKey->get($session->study_class_id.'|'.$lastWeekDate, new Collection());

                $result = $this->fill->computeRows($session, $class, $enrollments, $existingKeys, $lastWeekRows, $now);

                $rows = array_merge($rows, $result['rows']);
                $absent = array_merge($absent, $result['absent']);
                $resolvedSessions[] = $session;
            }

            $this->fill->upsertRows($rows);

            foreach ($resolvedSessions as $session) {
                $this->fill->resolveSession($session, $now);
            }

            return [$absent];
        });

        if ($absent !== []) {
            $this->fill->raiseAutoBlocks($absent, $actor);
        }
    }
}