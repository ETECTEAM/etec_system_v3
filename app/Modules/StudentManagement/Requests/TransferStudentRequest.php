<?php

namespace App\Modules\StudentManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAnyRole(['super_admin', 'admin']) === true; }
    public function rules(): array { return ['study_class_id' => ['required', 'integer', 'exists:study_classes,id'], 'force' => ['nullable', 'boolean']]; }
}
