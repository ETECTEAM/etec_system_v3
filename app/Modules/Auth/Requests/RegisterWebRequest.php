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
            // Honeypot: hidden from real users via CSS, but bots that auto-fill
            // every field on the form will populate it and fail validation here.
            'website' => ['prohibited'],
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