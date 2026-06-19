<?php

namespace App\Modules\Auth\Data;

/**
 * Carries validated login input from LoginWebRequest into AuthController.
 */
readonly class LoginData
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember,
    ) {}
}
