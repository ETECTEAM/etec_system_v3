<?php

namespace App\Modules\Enroll\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class MoveEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'study_class_id' => ['required', 'integer', 'exists:study_classes,id'],
            'force' => [
                'nullable', 'boolean',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value && ! $this->user()?->hasAnyRole(['super_admin', 'admin'])) {
                        $fail('Only a super admin or admin can move a student into a full class.');
                    }
                },
            ],
        ];
    }
}
