<?php

namespace App\Http\Requests\Auth;

use App\Data\Auth\VerifyCodeData;
use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
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
            'code' => ['required', 'digits:6'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function toData(): VerifyCodeData
    {
        $validated = $this->validated();

        return new VerifyCodeData(
            code: $validated['code'],
            userId: isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        );
    }
}
