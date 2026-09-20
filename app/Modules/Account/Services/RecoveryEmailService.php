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
    // Gmail ignores dots and "+tags" in the name part, so these all reach one inbox.
    private const GMAIL_DOMAINS = ['gmail.com', 'googlemail.com'];

    /**
     * One comparable form per real inbox: lower-cased, and for Gmail without dots, "+tag" or the
     * googlemail.com alias (a.b+x@googlemail.com and ab@gmail.com are the same mailbox).
     */
    public static function canonical(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', mb_strtolower(trim($email)), 2), 2, '');

        if (in_array($domain, self::GMAIL_DOMAINS, true)) {
            $local = str_replace('.', '', explode('+', $local, 2)[0]);
            $domain = 'gmail.com';
        }

        return "{$local}@{$domain}";
    }

    /**
     * True when this address already belongs to another account: as its login email, or as a
     * recovery email that account has verified. An unverified recovery email held by someone else
     * does not block it, so nobody can lock a person out by adding their address first; the
     * verification click checks again and only the first to verify keeps it.
     */
    public function takenByAnotherAccount(string $email, ?int $exceptUserId = null): bool
    {
        $canonical = self::canonical($email);
        $domain = substr((string) strrchr($canonical, '@'), 1);
        $domains = $domain === 'gmail.com' ? self::GMAIL_DOMAINS : [$domain];

        $sameInbox = fn (string $column, bool $verifiedOnly) => User::query()
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->when($verifiedOnly, fn ($query) => $query->where('recovery_verified', true))
            ->whereNotNull($column)
            ->where(fn ($query) => collect($domains)->each(fn (string $d) => $query->orWhere($column, 'like', "%@{$d}")))
            ->pluck($column)
            ->contains(fn (string $value): bool => self::canonical($value) === $canonical);

        return $sameInbox('email', false) || $sameInbox('recovery_email', true);
    }

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
