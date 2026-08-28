<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Modules\Attendance\Queries\HasApprovedPermission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records attendance on the instructor's behalf for one overdue session. Called by the
 * AutoRecordAttendance command for every session the query found due; each call is its
 * own transaction so one bad class can't roll back the whole scheduler run.
 */
class AutoRecordSession
{
    public function __construct(private readonly HasApprovedPermission $hasApprovedPermission) {}

    public function handle(int $sessionId): void
    {
        DB::transaction(function () use ($sessionId): void {
            // Locked and re-checked here, not just filtered by the caller's query: the
            // instructor could submit manually (InstructorClassService::saveAttendance,
            // which takes this same lock) in the gap between that query and this call.
            $session = ClassSession::query()->lockForUpdate()->find($sessionId);

            if (! $session || $session->status !== ClassSession::STATUS_PENDING) {
                return;
            }

            $now = Carbon::now('Asia/Phnom_Penh');

            // A class already over never gets auto-recorded after the fact — this is
            // what protects against a scheduler outage recording classes as "present"
            // hours or days after they actually happened.
            if ($now->greaterThanOrEqualTo($session->scheduled_end)) {
                $session->update(['status' => ClassSession::STATUS_MISSED]);

                return;
            }

            $enrollments = StudentEnrollment::query()
                ->where('study_class_id', $session->study_class_id)
                ->where('enrollment_status', 'active')
                ->get(['id', 'student_id']);

            if ($enrollments->isEmpty()) {
                $session->update(['status' => ClassSession::STATUS_SKIPPED]);

                return;
            }

            $graceMinutes = (int) setting('attendance.auto_record_grace_minutes', 15);
            $defaultStatus = setting('attendance.auto_record_default_status', 'present');
            // A stray config value never becomes "absent" here — the one rule this
            // feature can't violate, regardless of what's stored.
            $defaultStatus = in_array($defaultStatus, ['present', 'pending'], true) ? $defaultStatus : 'present';

            foreach ($enrollments as $enrollment) {
                $hasPermission = $this->hasApprovedPermission->handle(
                    $enrollment->student_id,
                    $session->study_class_id,
                    $session->session_date,
                );

                $this->insertAttendance($session, $enrollment, $hasPermission ? 'permission' : $defaultStatus);
            }

            // The instructor's own notification is this row: their attendance page reads
            // status/recorded_at and renders the override banner (see part F). There is
            // no per-instructor push channel to send to instead — the shared Notification
            // model has no per-user column and its feed is admin-only.
            $session->update([
                'status' => ClassSession::STATUS_AUTO_RECORDED,
                'recorded_at' => $now,
                'grace_minutes_used' => $graceMinutes,
            ]);
        });
    }

    private function insertAttendance(ClassSession $session, StudentEnrollment $enrollment, string $status): void
    {
        try {
            StudentAttendance::create([
                'study_class_id' => $session->study_class_id,
                'student_enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'tracked_by' => null,
                'attendance_date' => $session->session_date,
                'status' => $status,
                'source' => StudentAttendance::SOURCE_AUTO,
            ]);
        } catch (QueryException $e) {
            // Unique index on (study_class_id, student_enrollment_id, attendance_date):
            // a row already exists for this student today. That can only mean a manual
            // submit landed for this specific student without the session lock catching
            // it first (e.g. a row inserted directly, outside saveAttendance) — the row
            // that's already there wins, this one is silently skipped.
            if (! str_contains($e->getMessage(), 'student_attendance_unique_day')) {
                throw $e;
            }
        }
    }
}
