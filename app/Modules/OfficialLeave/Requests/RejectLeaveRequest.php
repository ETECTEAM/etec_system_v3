<?php

namespace App\Modules\OfficialLeave\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Please provide a reason for rejection.',
        ];
    }
}
