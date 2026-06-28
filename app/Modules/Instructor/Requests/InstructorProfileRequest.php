<?php

namespace App\Modules\Instructor\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstructorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('instructor');
    }

    public function rules(): array
    {
        $instructorData = $this->user()?->instructorData;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'instructor_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('instructor_data', 'instructor_code')->ignore($instructorData?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time'])],
            'shift_group' => ['required', Rule::in(['morning_afternoon', 'morning_evening', 'weekend_morning', 'weekend_afternoon', 'custom'])],
            'available_for_class' => ['boolean'],
        ];
    }
}
