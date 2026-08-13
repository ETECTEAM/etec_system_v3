<?php

namespace App\Modules\Enroll\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            // 'student_id' => ['required', 'integer', 'exists:users,id'],
            'force' => [
                'nullable', 'boolean',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value && ! $this->user()?->hasRole('super_admin')) {
                        $fail('Only a super admin can add a student to a full class.');
                    }
                },
            ],
        ];
    }
}
