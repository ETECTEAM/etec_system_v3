<?php

namespace App\Modules\Enroll\Requests;

use App\Rules\LatinName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Staff-only: hand-registering a walk-in student into a class from the
        // class list. Route middleware pins this to super_admin|admin|instructor;
        // instructor ownership is enforced in the controller. Public
        // self-registration is a separate path (ClassJoinController).
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new LatinName],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
        ];
    }
}
