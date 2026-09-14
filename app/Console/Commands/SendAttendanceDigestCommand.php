<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\Holiday;
use App\Models\Notification;
use App\Modules\Notification\Events\NotificationsUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * One notification a day summarizing auto-recorded/missed sessions — not per-event, per
 * the spec. Delivered through the existing admin-only dashboard notification feed
 * (NotificationController, admin-notifications channel); there is no per-instructor
 * push channel for the individual events themselves (see AutoRecordSession).
 */
class SendAttendanceDigestCommand extends Command
{
    protected $signature = 'attendance:send-digest';

    protected $description = 'Sends admins one daily notification summarizing today\'s auto-recorded and missed attendance sessions.';

    public function handle(): int
    {
        $today = Carbon::now('Asia/Phnom_Penh')->toDateString();

        if (Holiday::isHoliday($today)) {
            return self::SUCCESS;
        }

        $autoRecorded = ClassSession::query()
            ->whereDate('session_date', $today)
            ->where('status', ClassSession::STATUS_AUTO_RECORDED)
            ->count();

        $missed = ClassSession::query()
            ->whereDate('session_date', $today)
            ->where('status', ClassSession::STATUS_MISSED)
            ->count();

        if ($autoRecorded === 0 && $missed === 0) {
            return self::SUCCESS;
        }

        Notification::create([
            'title' => 'Attendance auto-record digest',
            'message' => sprintf(
                '%d class(es) had attendance auto-recorded today, and %d class(es) ended with no attendance submitted at all (marked missed, needs manual review).',
                $autoRecorded,
                $missed,
            ),
            'type' => 'attendance_digest',
            'is_read' => false,
        ]);

        NotificationsUpdated::dispatch();

        return self::SUCCESS;
    }
}
