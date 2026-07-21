<?php

namespace App\Modules\Auth\Services;

use App\Models\LoginLockout;
use App\Models\LoginLockoutSetting;
use App\Models\LoginLockoutTier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tracks escalating, account-wide login bans on top of the short-window
 * login+IP RateLimiter. Keyed on the account alone (not IP), so an attacker
 * rotating source IPs can't dodge an active ban. Backed by the
 * login_lockouts table (not cache) so admins can list and unblock
 * currently-banned accounts.
 */
class LoginLockoutService
{
    public function isEnabled(): bool
    {
        return LoginLockoutSetting::current()->is_enabled;
    }

    public function isBanned(string $login): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $lockout = $this->find($login);

        return $lockout !== null && $lockout->banned_until?->isFuture();
    }

    public function isHardBlocked(string $login): bool
    {
        $lockout = $this->find($login);

        return $lockout !== null && $lockout->is_hard_block && $lockout->banned_until?->isFuture();
    }

    // How many wrong attempts are free before offense 1 trips.
    public function freeAttempts(): int
    {
        return max(1, LoginLockoutSetting::current()->free_attempts);
    }

    // True once an account has ever tripped a lockout and that history
    // hasn't aged out yet (same clean-streak forgiveness registerFailure()
    // applies). Callers use this to tell offense 1 - which still needs a
    // burst of freeAttempts() wrong passwords - apart from every offense
    // after it, which escalates on a single wrong attempt, iPhone-style.
    public function hasOffenseHistory(string $login): bool
    {
        $lockout = $this->find($login);

        if (! $lockout || $lockout->offense_number <= 0) {
            return false;
        }

        $resetAfterHours = LoginLockoutSetting::current()->reset_after_hours;

        if ($lockout->last_offense_at && $lockout->last_offense_at->addHours($resetAfterHours)->isPast()) {
            return false;
        }

        return true;
    }

    public function secondsRemaining(string $login): int
    {
        $lockout = $this->find($login);

        if (! $lockout || ! $lockout->banned_until?->isFuture()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($lockout->banned_until));
    }

    // Call once a failed attempt pushes the short-window limiter to its cap.
    // Bumps the account's offense count and bans it for that offense's
    // configured duration. Once the offense count exceeds every configured
    // tier, the account is hard-blocked for reset_after_hours instead of
    // reusing the last tier's duration forever. Returns the number of
    // minutes applied, mainly for logging/testing.
    public function registerFailure(string $login): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $login = Str::lower($login);
        $resetAfterHours = LoginLockoutSetting::current()->reset_after_hours;

        $lockout = LoginLockout::query()->firstOrCreate(['login' => $login]);

        // A long-enough clean streak forgives prior offenses, same as the
        // old cache TTL used to.
        if ($lockout->last_offense_at && $lockout->last_offense_at->addHours($resetAfterHours)->isPast()) {
            $lockout->offense_number = 0;
        }

        $lockout->offense_number += 1;
        $lockout->last_offense_at = now();

        $tierCount = LoginLockoutTier::query()->count();

        if ($lockout->offense_number > $tierCount) {
            $minutes = $resetAfterHours * 60;
            $lockout->is_hard_block = true;
        } else {
            $tier = LoginLockoutTier::query()
                ->where('offense_number', '<=', $lockout->offense_number)
                ->orderByDesc('offense_number')
                ->first() ?? LoginLockoutTier::query()->orderBy('offense_number')->first();

            // No tiers configured at all - fall back to a safe 1-minute ban
            // rather than leaving the account unprotected.
            $minutes = $tier->duration_minutes ?? 1;
            $lockout->is_hard_block = false;
        }

        $lockout->banned_until = now()->addMinutes($minutes);
        $lockout->save();

        return $minutes;
    }

    // Call on a successful login - fully wipes the account's lockout
    // history, so the next lockout (if any) starts back at the first tier.
    public function clear(string $login): void
    {
        LoginLockout::query()->where('login', Str::lower($login))->delete();
    }

    // Admin-triggered early release: lifts the active ban but leaves the
    // offense history in place, so a hard-blocked account isn't fully
    // pardoned - if it fails again right away, it's hard-blocked again on
    // the very next offense. Only a successful login (clear()) resets
    // history.
    public function unblock(string $login): void
    {
        LoginLockout::query()
            ->where('login', Str::lower($login))
            ->update(['banned_until' => null, 'is_hard_block' => false]);
    }

    // Currently-banned accounts, for the admin unblock page.
    public function blocked(): Collection
    {
        return LoginLockout::query()
            ->where('banned_until', '>', now())
            ->orderBy('banned_until')
            ->get();
    }

    private function find(string $login): ?LoginLockout
    {
        return LoginLockout::query()->where('login', Str::lower($login))->first();
    }
}
