<?php

namespace App\Modules\Website\Requests;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Modules\Website\Services\PublicRegistrationCookie;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public self-registration on the /classes page — no login required.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $studyClass = $this->route('studyClass');

                    if (! $studyClass instanceof StudyClass) {
                        return;
                    }

                    $userIds = Student::query()->where('phone', $value)->pluck('user_id');

                    $alreadyRegistered = StudentEnrollment::query()
                        ->where('study_class_id', $studyClass->id)
                        ->where('enrollment_status', 'active')
                        ->whereIn('student_id', $userIds)
                        ->exists();

                    if ($alreadyRegistered) {
                        $fail('This phone number is already registered for this class.');

                        return;
                    }

                    // Backs up the phone check above: a returning visitor could type a
                    // different (or fake) phone number to dodge it, but this browser's
                    // signed cookie from its earlier registration still proves it already
                    // has an active seat in this class (see PublicRegistrationCookie).
                    if (app(PublicRegistrationCookie::class)->alreadyRegistered($this, $studyClass)) {
                        $fail('You have already registered for this class from this device.');
                    }
                },
            ],
        ];
    }
}
