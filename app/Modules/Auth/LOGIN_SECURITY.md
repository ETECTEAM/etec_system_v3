# Login Flow Security

How `AuthController::loginWeb()` protects against brute-force and account
enumeration, and what's still a known trade-off.

## Where the code lives

- Controller: [`Controllers/AuthController.php`](Controllers/AuthController.php) — `loginWeb()`
- Escalating account ban: [`Services/LoginLockoutService.php`](Services/LoginLockoutService.php), backed by the `login_lockouts` table ([`app/Models/LoginLockout.php`](../../Models/LoginLockout.php))
- Tier/settings admin page (`super_admin` only): [`Controllers/LoginSecuritySettingsController.php`](Controllers/LoginSecuritySettingsController.php), [`resources/js/pages/backend/login-security/Edit.vue`](../../../../resources/js/pages/backend/login-security/Edit.vue)
- Blocked-accounts admin page (`super_admin` + `admin`): [`Controllers/LoginLockoutController.php`](Controllers/LoginLockoutController.php), [`resources/js/pages/backend/login-security/BlockedAccounts.vue`](../../../../resources/js/pages/backend/login-security/BlockedAccounts.vue)
- Rate limiter config: [`config/auth.php`](../../../config/auth.php) (`dummy_password_hash`), [`app/Providers/AppServiceProvider.php`](../../Providers/AppServiceProvider.php) (`RateLimiter::for('login', ...)`)
- Routes: [`routes/web/backend/auth.php`](../../../routes/web/backend/auth.php) — `POST /login`; [`routes/web/backend/login-security.php`](../../../routes/web/backend/login-security.php) — settings + blocked-accounts pages
- Permissions (seeded in [`database/seeders/Permission/PermissionSeeder.php`](../../../database/seeders/Permission/PermissionSeeder.php)): `manage-login-security` (`super_admin` only — tier/reset config), `unblock-login-accounts` (`super_admin` + `admin` — view/unblock blocked accounts)

## 1. Two layers of rate limiting

**Short-window limiter (unchanged, per login+IP)** — every failed attempt
counts toward a 5-attempts/60-seconds cap, keyed on lowercased login + the
request's IP:

| Layer | Limit | Where |
|---|---|---|
| Route middleware | 5/min | `throttle:login` → `RateLimiter::for('login', ...)` in `AppServiceProvider` |
| In-controller | 5 attempts / 60s decay | `RateLimiter::tooManyAttempts()` / `hit()` / `clear()` in `loginWeb()` |

This window only throttles a burst from *one IP*. It exists to slow down a
single fast attacker before the second layer below even engages.

**Escalating account ban (new, per account — no IP in the key)** — the
moment a failed attempt pushes the short window to its 5-attempt cap,
`AuthController::loginWeb()` calls `LoginLockoutService::registerFailure()`,
which:

1. Bumps a per-account "offense count" (how many times this account has
   been locked out), remembered for an admin-configurable number of hours
   (`LoginLockoutSetting.reset_after_hours`, default 24) — after that many
   clean hours with no lockouts, the count drops back to zero.
2. Looks up the ban duration for that offense number from `LoginLockoutTier`
   (e.g. 1 min → 5 min → 15 min by default). **Once the offense count
   exceeds every configured tier, the account is hard-blocked instead** —
   banned for the full `reset_after_hours` window (not the last tier's short
   duration repeated forever). `loginWeb()` shows a different message for
   this case ("contact an administrator") instead of a countdown, and
   `LoginLockout.is_hard_block` records which kind of ban is active.
3. Bans the **account**, regardless of IP, for that duration.

Every `loginWeb()` call checks this ban first, before the short-window
limiter. Because the key has no IP in it, an attacker rotating source IPs
can no longer dodge an active ban — this closes the gap the short-window
limiter has on its own.

A successful login calls `LoginLockoutService::clear()` alongside
`RateLimiter::clear()`, deleting the account's `login_lockouts` row entirely
(full reset — offense count and any active ban both gone).

**Admin unblock:** a `super_admin` or `admin` can see every currently-banned
account and lift a ban early at `/dashboard/login-security/blocked-accounts`
(`unblock-login-accounts` permission). Unblocking only clears the active ban
(`LoginLockoutService::unblock()`) — it deliberately leaves the offense
count/history in place, so a hard-blocked account isn't fully pardoned; if
it fails again right away it's hard-blocked again on the very next attempt.
Only a successful login fully resets the history.

**Admin configuration:** a `super_admin` can edit the tier durations and the
reset window at `/dashboard/login-security` (gated by the
`manage-login-security` permission). Defaults are seeded by
`database/seeders/LoginLockoutSeeder.php`.

**Known gap:** the account owner isn't notified when either layer trips —
only the caller sees the error/lockout message.

## 2. No account enumeration

**Same error message for both cases.** Whether the login doesn't exist or
the password is wrong, the client always sees:

> "These credentials do not match our records."

The real reason (`email_not_found` vs `invalid_password`) is written to
`AuthAuditService` only — never returned to the client.

**Same response time for both cases.** `Hash::check()` (bcrypt) is
deliberately slow, so only running it when a user is found creates a timing
side-channel — a fast response reveals "no such account." The fix:

```php
$dummyHash = config('auth.dummy_password_hash');
$passwordMatches = Hash::check($data->password, $user?->password ?? $dummyHash);
```

`Hash::check()` always runs — against the real hash if the user exists, or a
fixed dummy bcrypt hash (same cost as `BCRYPT_ROUNDS`, set via
`AUTH_DUMMY_PASSWORD_HASH` in `.env`) if not. Both paths now cost the same.

**Verified live** (real HTTP requests, 5 runs each):

| Case | Reaches `Hash::check()`? | Timing |
|---|---|---|
| Nonexistent account, right domain (`ghost2@etec.com`) | Yes | ~216–241ms |
| Real account, wrong password (`superadmin@etec.com`) | Yes | ~230–243ms |

Indistinguishable — the fix works. (A third case, an off-domain address like
`nobody@example.com`, returns in ~20ms — but that's `LoginWebRequest`'s
`@etec.com`-only validation rule rejecting it *before* the controller runs,
not an enumeration leak. Don't use an off-domain address to test this.)

## 3. Account status handling

Status is only checked **after** the password has been verified, so an
attacker without valid credentials can't use this endpoint to probe whether
an account is active, pending, inactive, or rejected.

| Status | Behavior |
|---|---|
| `Active` | Logs in normally |
| `Inactive` | Blocked — "Your account is inactive. Please contact administrator." |
| `Rejected` | Blocked — "Your registration was rejected. Please contact support." |
| Anything else (e.g. `Pending`) | Not logged in yet — redirected to `/code-verify` (same flow `registerWeb` uses), so OTP-unverified accounts can't skip verification by just logging in |

**Known gap:** OTP only gates account *activation*, not every login. Once a
user is `Active`, they log in with password alone — there's no per-login 2FA.

## 4. Other things already handled

- **Session fixation** — `$request->session()->regenerate()` runs after every successful login/verification.
- **CSRF** — standard Laravel middleware; confirmed active during testing (required fetching an `XSRF-TOKEN` cookie first).
- **SQL injection** — `AuthService::findUserForLogin()` uses Eloquent's parameterized `where()`, no raw SQL.

## 5. Known gaps (not fixed — flagged for a future decision)

1. **No lockout notification.** When either rate-limit layer trips, the account owner isn't told — only the caller sees the error.
2. **No per-login 2FA.** OTP is activation-only (see §3).
3. **No audit trail for successful logins.** `AuthAuditService::log()` is only called on `login.failed` and `user.registered` — there's currently no record of *who logged in when*.
4. **Short-window `RateLimiter` is cache-backed, single-server assumption.** It stores state in the default cache store (`CACHE_STORE` in `.env`, currently `file`). That's correct on one app server, but a multi-instance deployment needs a shared store (e.g. Redis) or each instance throttles independently. (`LoginLockoutService` itself is database-backed — `login_lockouts` table — so it's already consistent across app servers regardless of cache driver.)

Each of these is marked with a `// NOTE:` comment at the relevant line in
`AuthController.php` so they're visible in context, not just here.
