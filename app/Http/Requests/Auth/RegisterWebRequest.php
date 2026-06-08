<?php

namespace App\Http\Requests\Auth;

use App\Data\Auth\RegisterUserData;
use Illuminate\Foundation\Http\FormRequest;

class RegisterWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
