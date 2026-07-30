<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Course;
use App\Models\Room;
use App\Models\StudyClass;
use Illuminate\Support\Facades\DB;

class UpdateStudyClass
{
    public function handle(StudyClass $studyClass, array $data): StudyClass
    {
        return DB::transaction(function () use ($studyClass, $data): StudyClass {
            $course = Course::query()->find($data['course_id']);
            $room = isset($data['room_id']) ? Room::query()->find($data['room_id']) : null;

            if (($data['class_type'] ?? null) === 'online') {
                $data['room_id'] = null;
            }

            $studyClass->update([
                'title' => $course?->title ?? $data['title'],
                'course_id' => $data['course_id'],
                'lesson_id' => $data['lesson_id'] ?? null,
                'teacher_id' => $data['teacher_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'class_type' => $data['class_type'],
                'status' => $data['status'],
                'study_days' => $data['study_days'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'capacity' => ($data['class_type'] ?? null) === 'physical' ? ($room?->capacity ?? $data['capacity']) : $data['capacity'],
                'price' => $data['price'],
                'enrollment_start_date' => $data['enrollment_start_date'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);

            return $studyClass;
        });
    }
}
