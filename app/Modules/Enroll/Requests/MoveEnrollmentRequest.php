<?php

namespace App\Modules\Enroll\Requests;

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
            'force' => ['nullable', 'boolean'],
        ];
    }
}
