<?php

namespace App\Events;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
