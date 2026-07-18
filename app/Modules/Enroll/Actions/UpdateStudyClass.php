<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudyClass;
use Illuminate\Support\Facades\DB;

class UpdateStudyClass
{
    public function handle(StudyClass $studyClass, array $data): StudyClass
    {
        return DB::transaction(function () use ($studyClass, $data): StudyClass {
            if (($data['class_type'] ?? null) === 'online') {
                $data['room_id'] = null;
            }

            $studyClass->update([
                'title' => $data['title'],
                'course_id' => $data['course_id'],
                'lesson_id' => $data['lesson_id'] ?? null,
                'teacher_id' => $data['teacher_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'class_type' => $data['class_type'],
                'status' => $data['status'],
                'study_days' => $data['study_days'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'capacity' => $data['capacity'],
                'price' => $data['price'],
                'enrollment_start_date' => $data['enrollment_start_date'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);

            return $studyClass;
        });
    }
}
