<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Course;
use App\Models\CourseClassTypeStatus;

/**
 * Opens or closes one course under one class type, leaving every other class
 * type - and the course's time slots, Max Classes and start dates - untouched.
 */
class SetCourseClassTypeStatus
{
    public function handle(Course $course, int $classTypeId, string $status): CourseClassTypeStatus
    {
        return CourseClassTypeStatus::query()->updateOrCreate(
            ['course_id' => $course->id, 'class_type_id' => $classTypeId],
            ['status' => $status],
        );
    }
}
