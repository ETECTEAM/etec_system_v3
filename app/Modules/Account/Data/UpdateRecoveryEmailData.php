<?php

namespace App\Modules\Account\Data;

/**
 * Carries validated input from UpdateRecoveryEmailRequest into AccountSecurityController.
 */
readonly class UpdateRecoveryEmailData
{
    public function __construct(
        public string $recoveryEmail,
    ) {}
}
