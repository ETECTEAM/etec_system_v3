<?php

namespace App\Modules\Auth\Data;

/**
 * Carries validated registration input before a pending user is created.
 */
readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
