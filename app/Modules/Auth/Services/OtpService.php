<?php

namespace App\Modules\Auth\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Creates and verifies one-time registration codes.
 */
class OtpService
{
    public function __construct(private readonly AuthAuditService $auditService) {}

    public function createForUser(User $user, ?int $createdBy = null): array
    {
        $plainCode = (string) random_int(100000, 999999);

        // Store only a hash so the plain OTP is never saved in the database.
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
        // Always verify against the latest unverified OTP for this user.
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

            // Failed attempts are audited so abuse can be investigated later.
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
