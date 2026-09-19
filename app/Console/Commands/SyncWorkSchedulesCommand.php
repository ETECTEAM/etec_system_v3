<?php

namespace App\Console\Commands;

use App\Models\InstructorData;
use App\Modules\Instructor\Services\InstructorProfileService;
use Illuminate\Console\Command;

class SyncWorkSchedulesCommand extends Command
{
    protected $signature = 'work-schedules:sync';

    protected $description = 'Re-seeds the work schedules and rebuilds every instructor\'s availability from them.';

    public function handle(InstructorProfileService $profiles): int
    {
        // WorkScheduleSeeder only rewrites the schedules; instructors keep their old availability rows until rebuilt below.
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\WorkSchedule\\WorkScheduleSeeder', '--force' => true]);

        $instructors = InstructorData::query()->whereNotNull('work_schedule_id')->get();

        foreach ($instructors as $instructor) {
            $profiles->generateInstructorAvailabilities($instructor);
        }

        $this->info("Work schedules seeded; availability rebuilt for {$instructors->count()} instructor(s).");

        return self::SUCCESS;
    }
}
