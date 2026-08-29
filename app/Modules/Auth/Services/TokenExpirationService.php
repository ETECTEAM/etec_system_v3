<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;

/**
 * Resolves how long an authenticated session/token stays valid based on the
 * user's backend role, and computes the absolute expiry from the issued time.
 *
 * The role is always read from the authenticated user's actual backend roles
 * (Spatie, web guard) - never from anything the frontend sent - and the
 * durations live in one place: config('auth.token_expiration.roles').
 */
class TokenExpirationService
{
    /**
     * Roles that carry their own token lifetime, in precedence order, so a
     * user holding several of them resolves to the most privileged one.
     */
    private const ROLE_PRECEDENCE = ['super_admin', 'admin', 'instructor'];

    public function durationForRole(string $role): ?DateInterval
    {
        $duration = config("auth.token_expiration.roles.{$role}");

        if ($duration === null) {
            return null;
        }

        return new DateInterval((string) $duration);
    }

    /**
     * The role that should drive this user's token lifetime, or null when the
     * user holds none of the expiring roles (keeps the never-expire policy).
     */
    public function roleFor(User $user): ?string
    {
        foreach (self::ROLE_PRECEDENCE as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    public function durationFor(User $user): ?DateInterval
    {
        $role = $this->roleFor($user);

        return $role !== null ? $this->durationForRole($role) : null;
    }

    /**
     * Absolute expiry = issued time + the role's duration. Returns null for
     * roles with no configured lifetime (they keep the existing behavior and
     * never expire).
     */
    public function expiresAt(User $user, DateTimeInterface|string|null $issuedAt = null): ?Carbon
    {
        $duration = $this->durationFor($user);

        if ($duration === null) {
            return null;
        }

        return Carbon::parse($issuedAt ?? now())->add($duration);
    }
}
