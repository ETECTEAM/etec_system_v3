<?php

namespace App\Modules\Enroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareClassInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Sharing splits the days only — both instructors keep the class's own time slot,
        // which the action copies from the class rather than taking from this request.
        return [
            'instructor_id' => ['required', 'integer', 'exists:users,id'],
            'instructor_term_id' => ['required', 'integer', 'exists:terms,id'],
            'instructor_subject' => ['nullable', 'string', 'max:255'],

            // The owner's own days, which the dialog lets them set at the same time so the
            // two halves of the class don't land on the same days.
            'owner_term_id' => ['required', 'integer', 'exists:terms,id'],
            'owner_subject' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Sharing a class splits the week between the two instructors, so they cannot
            // sit on the same days — the dialog hides one side's choice from the other.
            if (
                $this->filled(['owner_term_id', 'instructor_term_id'])
                && (int) $this->input('owner_term_id') === (int) $this->input('instructor_term_id')
            ) {
                $validator->errors()->add(
                    'instructor_term_id',
                    'Both instructors cannot teach on the same days. Give each their own days.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'instructor_id.required' => 'Choose the instructor to share this class with.',
            'instructor_term_id.required' => 'Choose which days that instructor teaches.',
            'owner_term_id.required' => 'Choose which days the class owner teaches.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'instructor_subject' => $this->filled('instructor_subject') ? trim($this->input('instructor_subject')) : null,
            'owner_subject' => $this->filled('owner_subject') ? trim($this->input('owner_subject')) : null,
        ]);
    }
}
