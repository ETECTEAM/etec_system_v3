<?php

namespace App\Modules\Account\Services;

use App\Models\User;
use App\Modules\Account\Notifications\RecoveryEmailChangedNotification;
use App\Modules\Account\Notifications\RecoveryEmailVerificationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Shared recovery-email add / change / resend logic, used by both the
 * standalone Account Security page and the instructor onboarding wizard so the
 * signed-link TTL and the "alert the login inbox" safeguard live in one place.
 */
class RecoveryEmailService
{
    /**
     * Overwrite the recovery email, drop its verified flag, and email a fresh
     * signed verification link. Returns false when that link could not be sent -
     * the address is still saved, and "Resend" retries once mail is working.
     */
    public function updateAndSendVerification(User $user, string $recoveryEmail): bool
    {
        // Deliberately no staging column: the account has zero verified recovery
        // email until the new link below is clicked.
        $user->forceFill([
            'recovery_email' => $recoveryEmail,
            'recovery_verified' => false,
        ])->save();

        $sent = $this->sendVerificationLink($user);

        // Alert the login inbox, not the (unverified) new recovery address, so a
        // compromised recovery email alone can't silently take over the account.
        try {
            Notification::route('mail', $user->email)
                ->notify(new RecoveryEmailChangedNotification($recoveryEmail));
        } catch (Throwable $e) {
            Log::warning('Failed to send recovery-email-changed alert', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $sent;
    }

    public function resendVerificationLink(User $user): bool
    {
        return $this->sendVerificationLink($user);
    }

    private function sendVerificationLink(User $user): bool
    {
        $url = URL::temporarySignedRoute(
            'account-security.recovery-email.verify',
            now()->addHours(24),
            ['user' => $user->id]
        );

        try {
            Notification::route('mail', $user->recovery_email)
                ->notify(new RecoveryEmailVerificationNotification($url));

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to send recovery-email verification link', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
