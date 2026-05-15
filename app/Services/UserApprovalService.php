<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\OtpVerification;
use App\Models\User;

class UserApprovalService
{
    public function __construct(private readonly AuthAuditService $auditService) {}

    public function approve(User $user, ?int $actorId = null, string $source = 'manual'): User
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'is_active' => true,
            'verified_at' => $user->verified_at ?? now(),
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
            'status' => UserStatus::Rejected,
            'is_active' => false,
        ])->save();

        $this->auditService->log($user, 'user.rejected', request()->ip(), [
            'source' => $source,
        ], $actorId);

        return $user;
    }

    private function markLatestOtpVerified(User $user): void
    {
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
