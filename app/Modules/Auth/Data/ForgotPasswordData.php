<?php

namespace App\Modules\Auth\Data;

/**
 * Carries validated input from ForgotPasswordRequest into AuthController.
 */
readonly class ForgotPasswordData
{
    public function __construct(
        public string $email,
    ) {}
}
