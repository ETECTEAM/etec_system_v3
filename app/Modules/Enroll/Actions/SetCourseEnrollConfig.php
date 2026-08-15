<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Course;
use App\Models\CourseEnrollConfig;

class SetCourseEnrollConfig
{
    // Always inserts a new schedule row. Never matches/reuses an existing row
    // by (course_id, time_id) - see update() for why that matters.
    public function create(Course $course, ?int $timeId, array $data): CourseEnrollConfig
    {
        return CourseEnrollConfig::query()->create([
            'course_id' => $course->id,
            'time_id' => $timeId,
            ...$this->attributes($data),
        ]);
    }

    // Updates this exact row in place, including its time_id when the admin
    // moves the schedule to a different slot. Previously this used
    // updateOrCreate(['course_id' => ..., 'time_id' => $newTimeId], ...),
    // which matches by attributes rather than by the row being edited - a
    // time_id change that didn't already have a matching row silently
    // created a brand-new CourseEnrollConfig instead of updating this one,
    // orphaning the original row and leaving the frontend holding whichever
    // id the mismatch happened to produce. Updating $config directly removes
    // that ambiguity: this row's id never changes.
    public function update(CourseEnrollConfig $config, array $data): CourseEnrollConfig
    {
        $config->update([
            'time_id' => array_key_exists('time_id', $data) ? $data['time_id'] : $config->time_id,
            ...$this->attributes($data),
        ]);

        return $config;
    }

    private function attributes(array $data): array
    {
        return [
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'unit_price' => $data['unit_price'] ?? 0,
            'course_price' => $data['course_price'] ?? 0,
            'selected_price_type' => $data['selected_price_type'] ?? CourseEnrollConfig::PRICE_TYPE_COURSE,
            'document_price' => $data['document_price'] ?? 5,
        ];
    }
}
