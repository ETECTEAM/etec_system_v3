<?php

namespace App\Modules\Enroll\Actions;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\Room;
use App\Models\StudyClass;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStudyClass
{
    public function __construct(private readonly InstructorAssignmentAvailability $instructorAvailability) {}

    public function handle(array $data): StudyClass
    {
        return DB::transaction(function () use ($data): StudyClass {
            $this->ensureInstructorIsAvailable($data);

            return StudyClass::create($this->payload($data));
        });
    }

    private function ensureInstructorIsAvailable(array $data): void
    {
        if (empty($data['teacher_id'])) {
            return;
        }

        $reason = $this->instructorAvailability->unavailableReason(
            (int) $data['teacher_id'],
            (int) $data['term_id'],
            (int) $data['time_id'],
        );

        if ($reason !== null) {
            throw ValidationException::withMessages(['teacher_id' => $reason]);
        }
    }

    private function payload(array $data): array
    {
        $course = Course::query()->find($data['course_id']);
        $room = isset($data['room_id']) ? Room::query()->find($data['room_id']) : null;
        $online = $this->isOnline($data['class_type_id'] ?? null);

        if ($online) {
            $data['room_id'] = null;
        }

        return [
            'title' => $course?->title ?? $data['title'],
            'course_id' => $data['course_id'],
            'lesson_id' => $data['lesson_id'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'class_type_id' => $data['class_type_id'] ?? null,
            'term_id' => $data['term_id'] ?? null,
            'time_id' => $data['time_id'] ?? null,
            'status' => $data['status'],
            'capacity' => $online ? $data['capacity'] : ($room?->capacity ?? $data['capacity']),
            'price' => $data['price'],
            'document_price' => $data['document_price'] ?? 0,
            'enrollment_start_date' => $data['enrollment_start_date'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ];
    }

    private function isOnline(mixed $classTypeId): bool
    {
        return $classTypeId
            ? ClassType::query()->find($classTypeId)?->isOnline() ?? false
            : false;
    }
}
