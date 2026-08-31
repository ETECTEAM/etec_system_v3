<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * After the override window expires, provisional auto-record rows that were still
 * waiting in "pending" are finalized to "absent". This is the second half of the
 * pre-attendance flow: the session stays auto-recorded for history, but the provisional
 * rows are no longer left hanging.
 */
class FinalizeAutoRecordedSession
{
    public function handle(int $sessionId): void
    {
        DB::transaction(function () use ($sessionId): void {
            $session = ClassSession::query()->lockForUpdate()->find($sessionId);

            if (! $session || $session->status !== ClassSession::STATUS_AUTO_RECORDED || ! $session->recorded_at) {
                return;
            }

            $overrideHours = (int) setting('attendance.auto_record_override_hours', 24);
            $deadline = $session->recorded_at->copy()->addHours($overrideHours);

            if (Carbon::now('Asia/Phnom_Penh')->lessThanOrEqualTo($deadline)) {
                return;
            }

            $justAbsent = StudentAttendance::query()
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $session->session_date)
                ->where('source', StudentAttendance::SOURCE_AUTO)
                ->where('status', 'pending')
                ->pluck('student_id');

            StudentAttendance::query()
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $session->session_date)
                ->where('source', StudentAttendance::SOURCE_AUTO)
                ->where('status', 'pending')
                ->update([
                    'status' => 'absent',
                    'updated_at' => Carbon::now('Asia/Phnom_Penh'),
                ]);

            // Raise / escalate absence blocks for the students the system just
            // finalized as absent (no instructor involved).
            $autoBlock = app(AutoBlockStudent::class);
            foreach ($justAbsent->unique() as $studentId) {
                $autoBlock->handle((int) $studentId, (int) $session->study_class_id, (string) $session->session_date);
            }
        });
    }
}
