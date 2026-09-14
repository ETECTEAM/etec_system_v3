<?php

namespace App\Modules\StudentManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAnyRole(['super_admin', 'admin']) === true; }

    public function rules(): array
    {
        return ['full_name' => ['required', 'string', 'max:255'], 'gender' => ['nullable', 'string', 'max:30'], 'phone' => ['nullable', 'string', 'max:40']];
    }
}
