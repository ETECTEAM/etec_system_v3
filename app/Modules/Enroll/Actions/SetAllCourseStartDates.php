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

        $rows = $courseIds->map(fn (int $courseId) => [
            'course_id' => $courseId,
            // Only used when a course has no config row yet — existing rows keep their status.
            'status' => 'closed',
            'start_date' => $startDate,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        CourseEnrollConfig::query()->upsert($rows, ['course_id'], ['start_date', 'updated_at']);

        return $courseIds->count();
    }
}
