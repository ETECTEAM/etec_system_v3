<?php

namespace App\Data\Auth;

readonly class VerifyCodeData
{
    public function __construct(
        public string $code,
        public ?int $userId,
    ) {}
}
