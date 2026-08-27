<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Data\RegisterUserData;
use Illuminate\Foundation\Http\FormRequest;

class RegisterWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Honeypot: the field must stay empty for real users, but bot-filled
            // values should fail validation.
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.max' => 'The website field is prohibited.',
        ];
    }

    public function toData(): RegisterUserData
    {
        $validated = $this->validated();

        return new RegisterUserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );
    }
}
