<?php

namespace App\Modules\Enroll\Requests;

use App\Rules\LatinName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public self-registration via the QR code an instructor shares — no login required.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new LatinName],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:20'],
            'attendance_pin' => ['nullable', 'string', 'min:4', 'max:32'],
        ];
    }
}
