<?php

namespace App\Modules\StudentManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'study_class_id' => ['required', 'integer', 'exists:study_classes,id'],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
