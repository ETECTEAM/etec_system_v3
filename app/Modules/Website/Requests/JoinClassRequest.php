<?php

namespace App\Modules\Website\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public "Request to Join" form on the /classes page — no login required.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
