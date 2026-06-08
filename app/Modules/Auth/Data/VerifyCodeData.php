<?php

namespace App\Modules\Auth\Data;

readonly class VerifyCodeData
{
    public function __construct(
        public string $code,
        public ?int $userId,
    ) {}
}
