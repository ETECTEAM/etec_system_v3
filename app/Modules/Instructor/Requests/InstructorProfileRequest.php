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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'instructor_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('instructor_data', 'instructor_code')->ignore($instructorData?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time'])],
            'shift_template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'url', 'max:255'],
            'github' => ['nullable', 'string', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'string', 'url', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpeg,png,jpg', 'max:5120'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
