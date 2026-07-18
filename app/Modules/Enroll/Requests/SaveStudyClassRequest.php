<?php

namespace App\Modules\Enroll\Requests;

use App\Modules\Enroll\Queries\GetClassFormOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudyClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
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
            'room_id' => ['required_if:class_type,physical', 'nullable', 'integer', 'exists:rooms,id'],
            'class_type' => ['required', 'string', Rule::in(GetClassFormOptions::CLASS_TYPES)],
            'status' => ['required', 'string', Rule::in(GetClassFormOptions::STATUSES)],
            'study_days' => ['required', 'array', 'min:1'],
            'study_days.*' => ['required', 'string', Rule::in(GetClassFormOptions::STUDY_DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'enrollment_start_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'after_or_equal:enrollment_start_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
