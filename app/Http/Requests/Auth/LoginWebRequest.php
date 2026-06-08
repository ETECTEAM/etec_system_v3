<?php

namespace App\Http\Requests\Auth;

use App\Data\Auth\LoginData;
use Illuminate\Foundation\Http\FormRequest;

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
            'login' => ['nullable', 'string', 'required_without:email'],
            'email' => ['nullable', 'string', 'required_without:login'],
            'password' => ['required', 'string'],
        ];
    }

    public function toData(): LoginData
    {
        $validated = $this->validated();

        return new LoginData(
            login: trim((string) ($validated['login'] ?? $validated['email'] ?? '')),
            password: $validated['password'],
            remember: $this->boolean('remember'),
        );
    }
}
