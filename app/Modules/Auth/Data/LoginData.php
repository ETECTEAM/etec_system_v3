<?php

namespace App\Modules\Auth\Data;

readonly class LoginData
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember,
    ) {}
}
