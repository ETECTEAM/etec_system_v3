<?php

namespace App\Modules\Account\Requests;

use App\Modules\Account\Data\UpdateRecoveryEmailData;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a recovery-email add/change submission and builds
 * UpdateRecoveryEmailData for the controller.
 */
class UpdateRecoveryEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recovery_email' => [
                'required', 'string', 'email', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strcasecmp(trim((string) $value), (string) $this->user()?->email) === 0) {
                        $fail('Your recovery email must be different from your login email.');
                    }
                },
            ],
        ];
    }

    public function toData(): UpdateRecoveryEmailData
    {
        $validated = $this->validated();

        return new UpdateRecoveryEmailData(
            recoveryEmail: trim((string) $validated['recovery_email']),
        );
    }
}
