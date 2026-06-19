<?php

namespace App\Modules\Auth\Events;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after registration creates a pending user and OTP.
 */
class PendingUserRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly OtpVerification $otp,
        public readonly string $plainCode,
    ) {}
}
