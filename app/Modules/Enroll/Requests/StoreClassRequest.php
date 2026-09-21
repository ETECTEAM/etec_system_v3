<?php

namespace App\Modules\EnRoll\Requests;

use App\Models\Course;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $course = $this->filled('course_id')
            ? Course::query()->select('id', 'title')->find($this->input('course_id'))
            : null;
        $room = $this->filled('room_id')
            ? Room::query()->select('id', 'capacity')->find($this->input('room_id'))
            : null;

        $this->merge([
            'title' => $course?->title ?? $this->input('title'),
            'capacity' => $this->input('class_type') === 'physical'
                ? ($room?->capacity ?? $this->input('capacity'))
                : $this->input('capacity'),
            'status' => $this->input('status') ?: 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'course_id'   => ['required', 'integer', 'exists:courses,id'],
            'lesson_id'   => ['nullable', 'integer', 'exists:course_lessons,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'floor_id'    => ['nullable', 'integer', 'exists:floors,id'],
            'room_id'     => ['nullable', 'integer', 'exists:rooms,id'],
            'term_id'     => ['nullable', 'integer', 'exists:terms,id'],
            'time_id'     => ['required', 'integer', 'exists:times,id'],
            'capacity'    => ['required', 'integer', 'min:1', 'max:9999'],
            'status'      => ['nullable', 'string', 'in:active,inactive,completed'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'    => 'The class title is required.',
            'course_id.required' => 'Please select a course for this class.',
            'capacity.required' => 'Please set a capacity for this class.',
            'capacity.min'      => 'Capacity must be at least 1.',
            'time_id.required'  => 'Please select a study time.',
            'time_id.exists'    => 'The selected time is invalid.',
        ];
    }
}
