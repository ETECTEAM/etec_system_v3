<?php

namespace App\Modules\Enroll\Actions;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Room;
use App\Models\StudyClass;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStudyClass
{
    public function __construct(private readonly InstructorAssignmentAvailability $instructorAvailability) {}

    public function handle(StudyClass $studyClass, array $data): StudyClass
    {
        return DB::transaction(function () use ($studyClass, $data): StudyClass {
            $this->ensureInstructorIsAvailable($studyClass, $data);
            $this->ensureClassSlotAvailable($studyClass, $data);

            $course = Course::query()->find($data['course_id']);
            $room = isset($data['room_id']) ? Room::query()->find($data['room_id']) : null;
            $online = $this->isOnline($data['class_type_id'] ?? null);

            if ($online) {
                $data['room_id'] = null;
            }

            $studyClass->update([
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
                'attendance_latitude' => $data['attendance_latitude'] ?? null,
                'attendance_longitude' => $data['attendance_longitude'] ?? null,
                'attendance_radius_meters' => $data['attendance_radius_meters'] ?? null,
                'enrollment_start_date' => $data['enrollment_start_date'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);

            $this->syncOwnerPivot($studyClass, $data);

            return $studyClass;
        });
    }

    // A shared/collapsed class records the owner's own days in study_class_instructors;
    // editing the class's term/time must keep that pivot row in step or attendance and
    // conflict checks keep reading a stale owner schedule.
    private function syncOwnerPivot(StudyClass $studyClass, array $data): void
    {
        if ($studyClass->teacher_id === null) {
            return;
        }

        $studyClass->instructors()
            ->wherePivot('user_id', $studyClass->teacher_id)
            ->update([
                'term_id' => $data['term_id'] ?? $studyClass->term_id,
                'time_id' => $data['time_id'] ?? $studyClass->time_id,
            ]);
    }

    // Race-safe backstop for SaveStudyClassRequest's slot-limit check. Only a move
    // into a different course + class type + term + time slot can push that slot
    // over its limit.
    private function ensureClassSlotAvailable(StudyClass $studyClass, array $data): void
    {
        $sameSlot = (int) $studyClass->course_id === (int) ($data['course_id'] ?? 0)
            && (int) $studyClass->class_type_id === (int) ($data['class_type_id'] ?? 0)
            && (int) $studyClass->term_id === (int) ($data['term_id'] ?? 0)
            && (int) $studyClass->time_id === (int) ($data['time_id'] ?? 0);

        if ($sameSlot) {
            return;
        }

        $config = CourseEnrollConfig::forClassSlot(
            $data['course_id'] ?? null,
            $data['class_type_id'] ?? null,
            $data['term_id'] ?? null,
            $data['time_id'] ?? null,
        );

        if ($config && $config->classSlotFull($studyClass->id, lock: true)) {
            throw ValidationException::withMessages([
                'time_id' => "This time slot is full for this course - only {$config->max_classes} allowed.",
            ]);
        }
    }

    private function ensureInstructorIsAvailable(StudyClass $studyClass, array $data): void
    {
        if (empty($data['teacher_id'])) {
            return;
        }

        if ((int) $studyClass->teacher_id === (int) $data['teacher_id']) {
            return;
        }

        $reason = $this->instructorAvailability->unavailableReason(
            (int) $data['teacher_id'],
            (int) $data['term_id'],
            (int) $data['time_id'],
            $studyClass->id,
        );

        if ($reason !== null) {
            throw ValidationException::withMessages(['teacher_id' => $reason]);
        }
    }

    private function isOnline(mixed $classTypeId): bool
    {
        return $classTypeId
            ? ClassType::query()->find($classTypeId)?->isOnline() ?? false
            : false;
    }
}
