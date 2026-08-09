# Registration Workflow

This document explains how instructor self-registration works end to end — from the signup form to an active, logged-in account. It complements `docs/notification-workflow.md`, which covers what happens once the admin-approval request goes out.

## Overview

```text
POST /instructor-register
  -> RegisterWebRequest validates input
  -> User + InstructorData + OtpVerification created (one DB transaction)
  -> Auth::login($user)  (logged in immediately, status still "pending")
  -> PendingUserRegistered dispatched -> Telegram + dashboard notifications sent
  -> Registrant redirected to /code-verify

Two ways out of "pending":
  1. Registrant enters the OTP code at /code-verify
  2. Admin approves via Telegram or the dashboard (bypasses code entry)
```

Note: this is the **instructor** signup form. The student-facing registration at `/register` is a separate, unrelated frontend flow.

## Step 1 — Form and validation

`RegisterWebRequest` (`app/Modules/Auth/Requests/RegisterWebRequest.php`) validates:

- `name` — required, string, max 255
- `email` — required, valid email, must match `@etec.com`, unique
- `password` — required, min 8, must be confirmed

`toData()` returns a `RegisterUserData` DTO with just `name`/`email`/`password`. Role and status are **not** client-supplied — they're hardcoded server-side.

## Step 2 — `registerWeb()` creates the account

`AuthController::registerWeb` (`app/Modules/Auth/Controllers/AuthController.php:63-113`), inside one DB transaction:

1. Creates the `User` with `role: instructor`, `status: pending`.
2. Ensures the Spatie `instructor` role exists and syncs it onto the user.
3. Creates an `InstructorData` row with a generated instructor code.
4. If OTP verification is enabled (`config('auth.otp.enabled', true)`), calls `OtpService::createForUser($user)` (`app/Modules/Auth/Services/OtpService.php:17`), which generates a random 6-digit code and stores only `Hash::make($plainCode)` — the plain code is never persisted, only returned in memory.

After the transaction commits:

- Logs a `user.registered` audit event.
- `Auth::login($user)` — the user is logged in immediately even though `status` is still `pending`. Access is gated later by status checks, not by withholding login.
- Session is regenerated.

## Step 3 — Branch on whether OTP is enabled

**OTP disabled:** the user is auto-approved immediately via `UserApprovalService::approve($user, null, 'otp_disabled')` and redirected straight to the dashboard/login.

**OTP enabled (default):**

- `pending_verification_user_id` is stashed in the session.
- `PendingUserRegistered::dispatch($user, $otp, $plainCode)` fires. See `docs/notification-workflow.md` for the full breakdown of what this triggers — in short, it sends the plain OTP code to admins over **both** Telegram and the dashboard notification feed.
- The browser is redirected to `/code-verify`.

## Step 4 — Verification page

`AuthController::showVerifyCode` (`app/Modules/Auth/Controllers/AuthController.php:116-143`) resolves the pending user from the session (or, if the session was lost, from the currently authenticated non-active user) and renders the verify-code page. It redirects away if the user is missing, rejected, or already active.

## Step 5 — Verifying the code

`AuthController::verifyCodeApi` (`app/Modules/Auth/Controllers/AuthController.php:146-189`):

1. Resolves the user: session → authenticated user → explicit `userId` in the payload.
2. Calls `OtpService::verify($user, $code)` (`app/Modules/Auth/Services/OtpService.php:37`), which checks the hash, enforces a 10-minute expiry and a max of 5 attempts, and always returns the same generic error message so failures can't be distinguished from each other.
3. On success, calls `UserApprovalService::approve($user, null, 'otp')`.
4. Logs the user in again, regenerates the session, clears `pending_verification_user_id`, and returns a redirect path based on the user's permissions.

## Step 6 — The admin-approval shortcut

Because the registrant needs an admin to relay the OTP code to them (there is no email/SMS delivery of the code — see `docs/notification-workflow.md`), admins can instead resolve the registration directly:

- **Telegram** — tapping Approve/Reject on the bot message.
- **Dashboard** — clicking Approve/Reject on the notification popup or the notifications page.

Both call `UserApprovalService::approve()`/`reject()` directly, which also force-marks the OTP row's `verified_at` — so the registrant never needs to type the code if an admin approves this way.

## What actually flips `status: pending -> active`

Every path funnels through the same call:

```php
UserApprovalService::approve(User $user, ?int $actorId, string $source)
```

| Source | Triggered from |
|---|---|
| `otp` | Registrant enters the code at `/code-verify` |
| `telegram` | Admin taps Approve in Telegram |
| `dashboard` | Admin clicks Approve on the dashboard notification |
| `otp_disabled` | Auto-approved at registration when OTP verification is turned off |

`approve()` sets `status: Active`, `is_active: true`, stamps `verified_at`/`email_verified_at`, and closes out the latest unverified OTP row — regardless of which of the four sources triggered it.
