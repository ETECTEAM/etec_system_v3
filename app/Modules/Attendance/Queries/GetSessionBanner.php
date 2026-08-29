<?php

namespace App\Modules\Attendance\Queries;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;

/**
 * What an instructor's attendance page needs to render the auto-record banner (part F):
 * whether today's session was auto-recorded, when, and whether they can still correct it.
 */
class GetSessionBanner
{
    public function handle(int $studyClassId, ?string $date = null): ?array
    {
        $date ??= Carbon::now('Asia/Phnom_Penh')->toDateString();

        $session = ClassSession::query()
            ->where('study_class_id', $studyClassId)
            ->whereDate('session_date', $date)
            ->first();

        if (! $session) {
            return null;
        }

        $overrideHours = (int) setting('attendance.auto_record_override_hours', 24);
        $allowOverride = (bool) setting('attendance.auto_record_allow_override', true);
        $deadline = $session->recorded_at ? $session->recorded_at->copy()->addHours($overrideHours) : null;
        $isPreAttendance = in_array($session->status, [
            ClassSession::STATUS_PRE_ATTENDANCE,
            ClassSession::STATUS_PARTIAL,
        ], true);

        return [
            'session_date' => $session->session_date->toDateString(),
            'status' => $session->status,
            'recorded_at' => $session->recorded_at?->format('Y-m-d H:i'),
            'is_pre_attendance' => $isPreAttendance,
            'can_override' => $session->status === ClassSession::STATUS_AUTO_RECORDED
                && $allowOverride
                && $deadline
                && Carbon::now('Asia/Phnom_Penh')->lessThanOrEqualTo($deadline),
            'override_deadline' => $deadline?->format('Y-m-d H:i'),
        ];
    }
}
