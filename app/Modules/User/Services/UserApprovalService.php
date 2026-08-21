<?php

namespace App\Modules\User\Services;

use App\Enums\UserStatus;
use App\Models\Notification;
use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\Auth\Services\AuthAuditService;
use App\Modules\Notification\Events\NotificationsUpdated;

/**
 * Activates or rejects users from OTP verification, Telegram approval, or
 * the dashboard - the one place all three paths converge, so the dashboard's
 * copy of the request (and any admin watching it live) is kept in sync
 * regardless of which path resolved it.
 */
class UserApprovalService
{
    public function __construct(private readonly AuthAuditService $auditService) {}

    public function approve(User $user, ?int $actorId = null, string $source = 'manual'): User
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'verified_at' => $user->verified_at ?? now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->markLatestOtpVerified($user);
        $this->syncDashboardNotification($user);

        $this->auditService->log($user, 'user.approved', request()->ip(), [
            'source' => $source,
        ], $actorId);

        return $user;
    }

    public function reject(User $user, ?int $actorId = null, string $source = 'manual'): User
    {
        $user->forceFill([
            'status' => UserStatus::Rejected,
        ])->save();

        $this->syncDashboardNotification($user);

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

    // The dashboard notification's approval_status is derived live from the
    // user's status, but is_read is its own column - without this it stays
    // stuck "unread" whenever the OTP path or Telegram resolves it, and no
    // open dashboard learns about the change until the fallback poll.
    private function syncDashboardNotification(User $user): void
    {
        $otpIds = OtpVerification::query()->where('user_id', $user->id)->pluck('id');

        if ($otpIds->isNotEmpty()) {
            Notification::query()
                ->whereIn('otp_verification_id', $otpIds)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        NotificationsUpdated::dispatch();
    }
}
