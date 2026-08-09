<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Data\ForgotPasswordData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a password-reset-link request and builds ForgotPasswordData for the controller.
 */
class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function toData(): ForgotPasswordData
    {
        $validated = $this->validated();

        return new ForgotPasswordData(
            email: trim((string) $validated['email']),
        );
    }
}
