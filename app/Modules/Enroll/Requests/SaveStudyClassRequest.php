<?php

namespace App\Modules\Enroll\Requests;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\CourseLesson;
use App\Models\Room;
use App\Models\Schedule;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveStudyClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $course = $this->input('course_id')
            ? Course::query()->find($this->input('course_id'))
            : null;

        $room = $this->input('room_id')
            ? Room::query()->find($this->input('room_id'))
            : null;

        $lessonId = $this->validLessonId();

        $this->merge([
            'title' => $course?->title ?? $this->input('title'),
            'lesson_id' => $lessonId,
            'price' => $this->filled('price') ? round((float) $this->input('price'), 2) : null,
            'document_price' => $this->filled('document_price') ? round((float) $this->input('document_price'), 2) : 0,
            'capacity' => $this->isOnline()
                ? $this->input('capacity')
                : ($room?->capacity ?? $this->input('capacity')),
        ]);
    }

    public function rules(): array
    {
        $editingStudyClass = $this->route('studyClass');
        $editingCurrentSchedule = $this->isEditingCurrentSchedule($editingStudyClass);

        $termRules = ['required', 'integer', 'exists:terms,id'];
        if (! $editingCurrentSchedule) {
            $termRules[] = Rule::exists('schedules', 'term_id')->where('class_type_id', $this->input('class_type_id'));
        }

        $timeRules = ['required', 'integer', 'exists:times,id'];
        if (! $editingCurrentSchedule) {
            $timeRules[] = Rule::exists('schedule_time', 'time_id')->where('schedule_id', $this->scheduleId());
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'lesson_id' => [
                'nullable',
                'integer',
                'exists:course_lessons,id',
                Rule::exists('course_lessons', 'id')->where('course_id', $this->input('course_id')),
            ],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            // Admins/super admins assign the class to an instructor without picking a room;
            // the assigned instructor fills in the room once they take ownership of the class.
            'room_id' => [
                Rule::requiredIf(
                    fn () => ! $this->isOnline() && ($this->user()?->hasRole('instructor') ?? false)
                ),
                'nullable', 'integer', 'exists:rooms,id',
            ],
            'class_type_id' => ['required', 'integer', 'exists:class_type,class_type_id'],
            'term_id' => $termRules,
            'time_id' => $timeRules,
            'status' => ['required', 'string', Rule::in(GetClassFormOptions::STATUSES)],
            'capacity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'document_price' => ['nullable', 'numeric', 'min:0'],
            'attendance_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'attendance_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'attendance_radius_meters' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'enrollment_start_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'after_or_equal:enrollment_start_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('teacher_id')) {
                    return;
                }

                $studyClass = $this->route('studyClass');

                if ($studyClass && (int) $studyClass->teacher_id === (int) $this->input('teacher_id')) {
                    return;
                }

                $reason = app(InstructorAssignmentAvailability::class)->unavailableReason(
                    (int) $this->input('teacher_id'),
                    (int) $this->input('term_id'),
                    (int) $this->input('time_id'),
                    $studyClass?->id,
                );

                if ($reason !== null) {
                    $validator->errors()->add('teacher_id', $reason);
                }
            },

            // Per-course time-slot class limit (set on the Enroll Config page's
            // time badges): block a create - or a move into a different slot -
            // once that course + class type + term + time already has
            // max_classes live classes.
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()
                    || ! $this->filled('time_id')
                    || ! $this->filled('course_id')
                    || ! $this->filled('class_type_id')
                    || ! $this->filled('term_id')) {
                    return;
                }

                $studyClass = $this->route('studyClass');

                // Editing a class that stays in the same slot never adds to the count.
                if ($studyClass
                    && (int) $studyClass->course_id === (int) $this->input('course_id')
                    && (int) $studyClass->class_type_id === (int) $this->input('class_type_id')
                    && (int) $studyClass->term_id === (int) $this->input('term_id')
                    && (int) $studyClass->time_id === (int) $this->input('time_id')) {
                    return;
                }

                $config = CourseEnrollConfig::forClassSlot(
                    $this->input('course_id'),
                    $this->input('class_type_id'),
                    $this->input('term_id'),
                    $this->input('time_id'),
                );

                if ($config && $config->classSlotFull($studyClass?->id)) {
                    $noun = $config->max_classes === 1 ? 'class' : 'classes';
                    $validator->errors()->add(
                        'time_id',
                        "This time slot is full for this course - only {$config->max_classes} {$noun} allowed. Raise the limit on the Enroll Config page, or end / cancel a class in this slot.",
                    );
                }
            },
        ];
    }

    private function isOnline(): bool
    {
        return $this->input('class_type_id')
            ? ClassType::query()->find($this->input('class_type_id'))?->isOnline() ?? false
            : false;
    }

    private function scheduleId(): ?int
    {
        if (! $this->input('class_type_id') || ! $this->input('term_id')) {
            return null;
        }

        return Schedule::query()
            ->where('class_type_id', $this->input('class_type_id'))
            ->where('term_id', $this->input('term_id'))
            ->value('id');
    }

    private function isEditingCurrentSchedule($studyClass): bool
    {
        return $studyClass
            && (int) $studyClass->term_id === (int) $this->input('term_id')
            && (int) $studyClass->time_id === (int) $this->input('time_id');
    }

    private function validLessonId(): ?int
    {
        if (! $this->filled('lesson_id') || ! $this->filled('course_id')) {
            return null;
        }

        $lessonId = (int) $this->input('lesson_id');
        $courseId = (int) $this->input('course_id');

        return CourseLesson::query()
            ->whereKey($lessonId)
            ->where('course_id', $courseId)
            ->exists()
                ? $lessonId
                : null;
    }
}
