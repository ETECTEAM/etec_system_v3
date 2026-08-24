<?php

namespace App\Modules\Website\Requests;

use App\Rules\LatinName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new LatinName],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'regex:/^[0-9]{9,12}$/'],
        ];
    }
}
