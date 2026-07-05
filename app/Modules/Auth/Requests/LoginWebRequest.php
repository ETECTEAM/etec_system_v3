<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Data\LoginData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the backend login form and builds LoginData for the controller.
 */
class LoginWebRequest extends FormRequest
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
            'login' => ['nullable', 'string', 'required_without:email', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) && ! preg_match('/^[a-zA-Z0-9._%+-]+@etec\.com$/', $value)) {
                    $fail('Only @etec.com email addresses are allowed to log in.');
                }
            }],
            'email' => ['nullable', 'string', 'required_without:login'],
            'password' => ['required', 'string'],
        ];
    }

    public function toData(): LoginData
    {
        $validated = $this->validated();

        // Accept either login or email so the login form can support both fields.
        return new LoginData(
            login: trim((string) ($validated['login'] ?? $validated['email'] ?? '')),
            password: $validated['password'],
            remember: $this->boolean('remember'),
        );
    }
}
