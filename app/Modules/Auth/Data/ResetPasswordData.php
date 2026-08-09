<?php

namespace App\Modules\Auth\Data;

/**
 * Carries validated input from ResetPasswordRequest into AuthController.
 */
readonly class ResetPasswordData
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public string $passwordConfirmation,
    ) {}
}
