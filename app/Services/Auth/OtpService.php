<?php

namespace App\Services\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private readonly AuthAuditService $auditService) {}

    public function createForUser(User $user, ?int $createdBy = null): array
    {
        $plainCode = (string) random_int(100000, 999999);

        $otp = OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'created_by' => $createdBy,
        ]);

        $this->auditService->log($user, 'otp.created', request()->ip(), [
            'otp_id' => $otp->id,
        ], $createdBy);

        return [$otp, $plainCode];
    }

    public function verify(User $user, string $code): OtpVerification
    {
        $otp = OtpVerification::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Too many attempts. Please request a new code.'],
            ]);
        }

        if (! Hash::check($code, $otp->otp_code)) {
            $otp->increment('attempts');

            $this->auditService->log($user, 'otp.failed', request()->ip(), [
                'otp_id' => $otp->id,
                'attempts' => $otp->attempts + 1,
            ]);

            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        $otp->forceFill([
            'verified_at' => now(),
        ])->save();

        $this->auditService->log($user, 'otp.verified', request()->ip(), [
            'otp_id' => $otp->id,
        ]);

        return $otp;
    }
}
