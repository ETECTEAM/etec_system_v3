<?php

namespace App\Modules\StudentManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) === true;
    }

    public function rules(): array
    {
        return [
            'study_class_id' => ['required', 'integer', 'exists:study_classes,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
