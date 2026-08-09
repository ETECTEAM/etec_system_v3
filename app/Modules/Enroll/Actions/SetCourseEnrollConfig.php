<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Course;
use App\Models\CourseEnrollConfig;

class SetCourseEnrollConfig
{
    public function handle(Course $course, array $data): CourseEnrollConfig
    {
        return CourseEnrollConfig::query()->updateOrCreate(
            ['course_id' => $course->id],
            [
                'status' => $data['status'],
                'start_date' => $data['start_date'] ?? null,
            ]
        );
    }
}
