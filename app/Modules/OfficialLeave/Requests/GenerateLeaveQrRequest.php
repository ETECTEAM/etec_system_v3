<?php

namespace App\Modules\OfficialLeave\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLeaveQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ];
    }
}
