<?php

namespace App\Modules\User\Services;

use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\Auth\Services\AuthAuditService;

/**
 * Activates or rejects users from OTP verification or Telegram approval.
 */
class UserApprovalService
{
    public function __construct(private readonly AuthAuditService $auditService) {}

    public function approve(User $user, ?int $actorId = null, string $source = 'manual'): User
    {
        $user->forceFill([
            'status' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->markLatestOtpVerified($user);

        $this->auditService->log($user, 'user.approved', request()->ip(), [
            'source' => $source,
        ], $actorId);

        return $user;
    }

    public function reject(User $user, ?int $actorId = null, string $source = 'manual'): User
    {
        $user->forceFill([
            'status' => false,
        ])->save();

        $this->auditService->log($user, 'user.rejected', request()->ip(), [
            'source' => $source,
        ], $actorId);

        return $user;
    }

    private function markLatestOtpVerified(User $user): void
    {
        // Telegram approval should also close the latest pending OTP.
        $otp = OtpVerification::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if ($otp) {
            $otp->forceFill([
                'verified_at' => now(),
            ])->save();
        }
    }
}
