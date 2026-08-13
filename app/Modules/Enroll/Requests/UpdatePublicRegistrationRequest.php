<?php

namespace App\Modules\Enroll\Requests;

use App\Models\StudentEnrollment;
use App\Rules\LatinName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var StudentEnrollment $enrollment */
        $enrollment = $this->route('enrollment');
        $studentId = $enrollment->student?->id;

        return [
            'name' => ['required', 'string', 'max:255', new LatinName],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique('students', 'phone')->ignore($studentId),
            ],
        ];
    }
}
