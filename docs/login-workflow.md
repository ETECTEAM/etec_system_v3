# Login Workflow

This document explains how `AuthController::loginWeb` authenticates a user, including the timing-safe password check and the two-layer lockout system stacked in front of it. It complements `docs/registration-workflow.md` and `docs/notification-workflow.md`.

## Overview

```text
POST /login
  -> LoginWebRequest validates input (login/email + password)
  -> Account-wide ban check (LoginLockoutService::isBanned)
  -> Per login+IP rate limit (RateLimiter) - skipped once the account has offense history
  -> User lookup + constant-time password check (always runs, even for unknown logins)
  -> Wrong password -> escalate lockout, generic error
  -> Right password, but status != active -> redirect to /code-verify
  -> Right password, status == active -> Auth::login(), redirect to dashboard
```

## Step 1 — Validate input

`LoginWebRequest` (`app/Modules/Auth/Requests/LoginWebRequest.php`) accepts either a `login` or an `email` field (so the same form field can hold a username or an email), requires `password`, and — if the value looks like an email — enforces the `@etec.com` domain restriction. `toData()` builds a `LoginData` DTO with `login`, `password`, `remember`.

## Step 2 — Account-wide ban check (checked first)

`AuthController::loginWeb` (`app/Modules/Auth/Controllers/AuthController.php:191-283`) lowercases the login into `$loginKey` and immediately checks `LoginLockoutService::isBanned($loginKey)` (`app/Modules/Auth/Services/LoginLockoutService.php:25`).

This check is account-wide, not IP-based, and runs **before** anything else — specifically so a banned account can't burn through a fresh set of attempts just by switching IP. If banned, the request short-circuits straight to `bannedResponse()` (Step 6).

## Step 3 — Per login+IP rate limiting (only pre-first-offense)

```php
$hasOffenseHistory = $this->lockoutService->hasOffenseHistory($loginKey);
$limiterKey = $loginKey.'|'.$request->ip();
```

`hasOffenseHistory()` (`LoginLockoutService.php:54`) is true once an account has ever tripped a lockout and that history hasn't aged out (`reset_after_hours`).

- **No offense history yet:** the login+IP pair gets a burst of `freeAttempts()` (from `LoginLockoutSetting`) tracked by Laravel's `RateLimiter`. Exceeding it throws a `ValidationException` with a retry-after message.
- **Offense history exists:** the free-attempts limiter is skipped entirely — every wrong password from here on escalates the lockout immediately (comment in the code calls this "iPhone-style": the first lockout needs a burst of wrong guesses, every one after it trips on a single miss).

## Step 4 — Timing-safe credential check

```php
$user = $this->authService->findUserForLogin($data->login);
$dummyHash = config('auth.dummy_password_hash');
$passwordMatches = Hash::check($data->password, $user?->password ?? $dummyHash);
```

`AuthService::findUserForLogin()` (`app/Modules/Auth/Services/AuthService.php`) looks the user up by `email` if the input looks like an email, otherwise by `name`.

`Hash::check()` **always runs** — against a configured dummy hash when no user was found — so a nonexistent login takes the same time to reject as a real one with a wrong password. This closes the timing side-channel that would otherwise let an attacker enumerate valid logins.

## Step 5 — On failure: escalate and log, but never reveal which part was wrong

If the user doesn't exist or the password doesn't match:

```php
if ($hasOffenseHistory) {
    $this->lockoutService->registerFailure($loginKey);
} else {
    RateLimiter::hit($limiterKey, 60);
    if (RateLimiter::attempts($limiterKey) >= $this->lockoutService->freeAttempts()) {
        $this->lockoutService->registerFailure($loginKey);
    }
}
```

`registerFailure()` (`LoginLockoutService.php:88`) bumps `offense_number`, looks up the matching `LoginLockoutTier` for that offense count, and sets `banned_until` for that tier's duration. Once `offense_number` exceeds every configured tier, the account is **hard-blocked** for `reset_after_hours` instead of reusing the last tier's duration forever.

The real reason (`invalid_password` vs `email_not_found`) is written only to the audit log (`user.registered`-style event `login.failed`) — the response to the client is always the same generic message: *"These credentials do not match our records."* If this failure just tripped the ban, the response upgrades to `bannedResponse()` instead.

## Step 6 — `bannedResponse()`

Shared by the upfront `isBanned()` short-circuit and the failure branch that just tripped it, so both report identically (`app/Modules/Auth/Controllers/AuthController.php:372-394`):

- `isHardBlocked()` → *"Your account has been blocked due to repeated failed login attempts. Contact an administrator..."*
- Otherwise → *"Too many failed login attempts. Please try again in {seconds} seconds."*

Returns JSON with `retry_after`/`is_hard_block` for API clients, or a redirect back with flashed errors for the web form. Either way it sends a `Retry-After` header.

## Step 7 — Password correct: status gate

Only after the password has actually matched does the controller reveal anything about account status — so status can't be probed without valid credentials:

- `status === Inactive` → generic "account is inactive" error.
- `status === Rejected` → "registration was rejected" error.
- `status !== Active` (i.e. still `pending`) → stashes `pending_verification_user_id` in the session and redirects to `/code-verify`, same as a fresh registration would. See `docs/registration-workflow.md` for what happens there.

## Step 8 — Success

```php
RateLimiter::clear($limiterKey);
$this->lockoutService->clear($loginKey);
Auth::login($user, $data->remember);
$user->forceFill(['last_login_at' => now()])->save();
$request->session()->regenerate();
```

A successful login fully wipes the account's lockout history (`clear()`) — so any future lockout starts back at the first, shortest tier — and clears the short-window rate limiter. The user is redirected based on permissions (`redirectPathFor()`: `/dashboard` if they can view it, otherwise `/login`).

## Lockout model summary

| Concept | Backing | Scope | Cleared by |
|---|---|---|---|
| Free-attempt burst | Laravel `RateLimiter` (cache) | `login\|ip`, only before first offense | 60s TTL, or a successful login |
| Escalating ban | `LoginLockout` + `LoginLockoutTier` (DB) | account (`login` alone) | `clear()` on successful login |
| Hard block | Same `LoginLockout` row, `is_hard_block` flag | account | Admin `unblock()`, or `reset_after_hours` passing |

Because the account-wide ban is keyed on the login alone (not IP), rotating source IPs never helps an attacker once the free-attempt burst is exhausted — only a clean login resets the account back to a fresh state.
