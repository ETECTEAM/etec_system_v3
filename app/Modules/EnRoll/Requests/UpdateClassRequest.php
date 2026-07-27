<?php

namespace App\Modules\EnRoll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'course_id'   => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_id'   => ['nullable', 'integer', 'exists:course_lessons,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'floor_id'    => ['nullable', 'integer', 'exists:floors,id'],
            'room_id'     => ['nullable', 'integer', 'exists:rooms,id'],
            'term_id'     => ['nullable', 'integer', 'exists:terms,id'],
            'time_id'     => ['sometimes', 'required', 'integer', 'exists:times,id'],
            'capacity'    => ['sometimes', 'required', 'integer', 'min:1', 'max:9999'],
            'status'      => ['nullable', 'string', 'in:active,inactive,completed'],
        ];
    }
}
