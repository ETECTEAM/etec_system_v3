<?php

namespace App\Console\Commands;

use App\Modules\Attendance\Actions\GenerateClassSessions;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateClassSessionsCommand extends Command
{
    protected $signature = 'attendance:generate-sessions';

    protected $description = 'Creates today\'s ClassSession rows for every class meeting today.';

    public function handle(GenerateClassSessions $generate): int
    {
        $result = $generate->handle(Carbon::now('Asia/Phnom_Penh'));

        $this->info(sprintf(
            'Sessions generated: %d created, %d skipped (no students), %d skipped (no schedule match).',
            $result['created'],
            $result['skipped_no_students'],
            $result['skipped_no_match'],
        ));

        return self::SUCCESS;
    }
}
