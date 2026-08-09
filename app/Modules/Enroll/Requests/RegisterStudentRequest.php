<?php

namespace App\Modules\Enroll\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:20'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'time_id' => ['required', 'integer', 'exists:times,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'document_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
