<?php

namespace App\Data\Auth;

readonly class LoginData
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember,
    ) {}
}
