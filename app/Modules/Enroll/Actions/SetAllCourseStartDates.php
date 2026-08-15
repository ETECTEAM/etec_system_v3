<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use Illuminate\Support\Carbon;

class SetAllCourseStartDates
{
    public function handle(?string $startDate): int
    {
        $courseIds = Course::query()->pluck('id');

        if ($courseIds->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();

        // Applies to every schedule a course offers (one config row per time slot).
        CourseEnrollConfig::query()
            ->whereIn('course_id', $courseIds)
            ->update(['start_date' => $startDate, 'updated_at' => $now]);

        $coursesWithoutConfig = Course::query()
            ->whereIn('id', $courseIds)
            ->whereDoesntHave('enrollConfigs')
            ->pluck('id');

        $rows = $coursesWithoutConfig->map(fn (int $courseId) => [
            'course_id' => $courseId,
            'time_id' => null,
            // Only used when a course has no config row yet — existing rows keep their status.
            'status' => 'closed',
            'start_date' => $startDate,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            CourseEnrollConfig::query()->insert($rows);
        }

        return $courseIds->count();
    }
}
