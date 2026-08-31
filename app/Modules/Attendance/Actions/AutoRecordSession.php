<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\StudentEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Moves unresolved attendance to the recoverable pre-attendance flow after grace time.
 */
class AutoRecordSession
{
    public function handle(int $sessionId): void
    {
        DB::transaction(function () use ($sessionId): void {
            // Locked and re-checked here, not just filtered by the caller's query: the
            // instructor could submit manually (InstructorClassService::saveAttendance,
            // which takes this same lock) in the gap between that query and this call.
            $session = ClassSession::query()->lockForUpdate()->find($sessionId);

            if (! $session || ! in_array($session->status, [
                ClassSession::STATUS_PENDING,
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
            ], true)) {
                return;
            }

            $now = Carbon::now('Asia/Phnom_Penh');

            $enrollments = StudentEnrollment::query()
                ->where('study_class_id', $session->study_class_id)
                ->where('enrollment_status', 'active')
                ->get(['id', 'student_id']);

            if ($enrollments->isEmpty()) {
                $session->update(['status' => ClassSession::STATUS_SKIPPED]);

                return;
            }

            $graceMinutes = (int) setting('attendance.auto_record_grace_minutes', 15);
            $trackedCount = DB::table('student_attendances')
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $session->session_date)
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->count();

            $session->update([
                'status' => match (true) {
                    $trackedCount === 0 => ClassSession::STATUS_PRE_ATTENDANCE,
                    $trackedCount < $enrollments->count() => ClassSession::STATUS_PARTIAL,
                    default => ClassSession::STATUS_AUTO_RECORDED,
                },
                'recorded_at' => $now,
                'grace_minutes_used' => $graceMinutes,
            ]);
        });
    }
}
