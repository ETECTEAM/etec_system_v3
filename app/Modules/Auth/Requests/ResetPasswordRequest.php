<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Data\ResetPasswordData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a password reset submission and builds ResetPasswordData for the controller.
 */
class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toData(): ResetPasswordData
    {
        $validated = $this->validated();

        return new ResetPasswordData(
            token: $validated['token'],
            email: trim((string) $validated['email']),
            password: $validated['password'],
            passwordConfirmation: (string) $this->input('password_confirmation'),
        );
    }
}
