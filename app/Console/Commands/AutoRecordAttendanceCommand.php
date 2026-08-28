<?php

namespace App\Console\Commands;

use App\Modules\Attendance\Actions\AutoRecordSession;
use App\Modules\Attendance\Actions\FinalizeAutoRecordedSession;
use App\Modules\Attendance\Queries\GetSessionsDueForAutoRecord;
use App\Modules\Attendance\Queries\GetSessionsDueForAutoFinalize;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoRecordAttendanceCommand extends Command
{
    protected $signature = 'attendance:auto-record';

    protected $description = 'Processes attendance sessions after the grace window and finalizes overdue auto-recorded rows.';

    public function handle(
        GetSessionsDueForAutoRecord $dueSessions,
        GetSessionsDueForAutoFinalize $dueFinalizations,
        AutoRecordSession $autoRecord,
        FinalizeAutoRecordedSession $finalizeAutoRecorded,
    ): int
    {
        if (! setting('attendance.auto_record_enabled', true)) {
            return self::SUCCESS;
        }

        $now = Carbon::now('Asia/Phnom_Penh');
        $graceMinutes = (int) setting('attendance.auto_record_grace_minutes', 15);

        $sessionIds = $dueSessions->handle($now, $graceMinutes)->unique();

        foreach ($sessionIds as $sessionId) {
            $autoRecord->handle($sessionId);
        }

        $finalizationIds = $dueFinalizations->handle($now, (int) setting('attendance.auto_record_override_hours', 24));

        foreach ($finalizationIds as $sessionId) {
            $finalizeAutoRecorded->handle($sessionId);
        }

        $processed = $sessionIds->count() + $finalizationIds->count();

        if ($processed > 0) {
            $this->info("Processed {$processed} session(s).");
        }

        return self::SUCCESS;
    }
}
