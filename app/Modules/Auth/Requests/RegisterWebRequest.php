<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Data\RegisterUserData;
use App\Rules\ValidTurnstile;
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
            // Cloudflare Turnstile token. Only actually required once
            // TURNSTILE_SECRET_KEY is configured - ValidTurnstile itself is a
            // no-op without it, so this stays harmless in local/dev. The
            // automated test suite forces this key empty (see phpunit.xml),
            // since it can't solve a real challenge.
            'cf_turnstile_response' => [
                config('services.turnstile.secret_key') ? 'required' : 'nullable',
                'string',
                new ValidTurnstile($this->ip()),
            ],
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
