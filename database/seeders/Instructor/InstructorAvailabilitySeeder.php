<?php

namespace Database\Seeders\Instructor;

use App\Models\InstructorData;
use App\Models\WorkSchedule;
use App\Modules\Instructor\Services\InstructorProfileService;
use Illuminate\Database\Seeder;

class InstructorAvailabilitySeeder extends Seeder
{
    /**
     * Links every seeded instructor to a work schedule and generates their
     * InstructorAvailability rows from it - without this, InstructorData.
     * work_schedule_id stays null and instructor_availabilities stays empty,
     * so RegisterStudentForSchedule::availableInstructor() can never match
     * anyone and every public registration falls through to a pending
     * registration regardless of which course/term/time was picked.
     */
    public function run(): void
    {
        $fullTimeScheduleIds = WorkSchedule::query()
            ->where('code', 'like', 'full_time_%')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($fullTimeScheduleIds->isEmpty()) {
            return;
        }

        $service = app(InstructorProfileService::class);

        InstructorData::query()->orderBy('id')->get()->each(function (InstructorData $instructor, int $index) use ($fullTimeScheduleIds, $service): void {
            $instructor->update([
                'work_schedule_id' => $fullTimeScheduleIds[$index % $fullTimeScheduleIds->count()],
            ]);

            $service->generateInstructorAvailabilities($instructor->fresh());
        });
    }
}
