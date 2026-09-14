<?php

namespace App\Modules\Enroll\Actions;

use App\Models\CourseEnrollConfig;
use App\Models\Schedule;
use Illuminate\Support\Carbon;

/**
 * Applies one start date to every course's enrollment config for a single
 * class type, leaving every other class type untouched.
 *
 * The date lands on the schedule-scoped course_enroll_configs rows (the ones
 * carrying schedule_id), so Physical Class, Scholarship Class, Online Class …
 * each keep an independent start date per course.
 */
class SetClassTypeStartDates
{
    public function handle(int $classTypeId, ?string $startDate): int
    {
        $scheduleIds = Schedule::query()
            ->where('class_type_id', $classTypeId)
            ->pluck('id');

        if ($scheduleIds->isEmpty()) {
            return 0;
        }

        return CourseEnrollConfig::query()
            ->whereIn('schedule_id', $scheduleIds)
            ->update([
                'start_date' => $startDate,
                'updated_at' => Carbon::now(),
            ]);
    }
}
