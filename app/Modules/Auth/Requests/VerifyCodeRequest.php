<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Data\VerifyCodeData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates OTP verification input and builds VerifyCodeData.
 */
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

        // user_id is optional because the session usually identifies the pending user.
        return new VerifyCodeData(
            code: $validated['code'],
            userId: isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        );
    }
}
