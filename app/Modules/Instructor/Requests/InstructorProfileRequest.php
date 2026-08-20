<?php

namespace App\Modules\Instructor\Requests;

use App\Models\SubCategory;
use App\Models\WorkSchedule;
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
            'specialization' => ['nullable', 'array'],
            'specialization.*' => ['string', Rule::in(SubCategory::where('status', 'active')->pluck('name'))],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time'])],
            'work_schedule_id' => [
                'nullable',
                'integer',
                'exists:work_schedules,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && ! WorkSchedule::whereKey($value)
                        ->where('code', 'like', $this->input('employment_type').'_%')
                        ->exists()) {
                        $fail('The selected work schedule is not valid for the employment type.');
                    }
                },
            ],
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
