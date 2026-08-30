<?php

namespace App\Modules\Instructor\Requests;

use App\Models\SubCategory;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the teaching-setup step of the instructor onboarding wizard - the
 * three InstructorData fields the onboarding gate requires, nothing else.
 */
class OnboardingTeachingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('instructor');
    }

    public function rules(): array
    {
        return [
            'employment_type' => ['required', Rule::in(['full_time', 'part_time'])],
            'work_schedule_id' => [
                'required',
                'integer',
                'exists:work_schedules,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! WorkSchedule::whereKey($value)
                        ->where('code', 'like', $this->input('employment_type').'_%')
                        ->exists()) {
                        $fail('The selected work schedule is not valid for the employment type.');
                    }
                },
            ],
            'specialization' => ['required', 'array', 'min:1'],
            'specialization.*' => ['string', Rule::in(SubCategory::where('status', 'active')->pluck('name'))],
        ];
    }
}
