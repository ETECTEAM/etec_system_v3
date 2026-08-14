<?php

namespace App\Console\Commands;

use App\Modules\Attendance\Actions\AutoRecordSession;
use App\Modules\Attendance\Queries\GetSessionsDueForAutoRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoRecordAttendanceCommand extends Command
{
    protected $signature = 'attendance:auto-record';

    protected $description = 'Records attendance for sessions past their grace period with nothing submitted, and marks sessions past end time as missed.';

    public function handle(GetSessionsDueForAutoRecord $dueSessions, AutoRecordSession $autoRecord): int
    {
        if (! setting('attendance.auto_record_enabled', true)) {
            return self::SUCCESS;
        }

        $now = Carbon::now('Asia/Phnom_Penh');
        $graceMinutes = (int) setting('attendance.auto_record_grace_minutes', 15);

        // Both sets are handed to the same action: it re-locks and re-checks each
        // session itself, so a session that crossed from "due" to "past end" between
        // the query above and its own turn in this loop still resolves correctly.
        $sessionIds = $dueSessions->handle($now, $graceMinutes)
            ->merge($dueSessions->pastEnd($now))
            ->unique();

        foreach ($sessionIds as $sessionId) {
            $autoRecord->handle($sessionId);
        }

        if ($sessionIds->isNotEmpty()) {
            $this->info("Processed {$sessionIds->count()} session(s).");
        }

        return self::SUCCESS;
    }
}
