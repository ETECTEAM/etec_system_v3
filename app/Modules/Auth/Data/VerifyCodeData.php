<?php

namespace App\Modules\Auth\Data;

/**
 * Carries the submitted OTP code and optional user fallback id.
 */
readonly class VerifyCodeData
{
    public function __construct(
        public string $code,
        public ?int $userId,
    ) {}
}
