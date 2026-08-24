# Auth Module Workflow

This document reflects the current module-first authentication flow implemented across `AuthController`, auth requests, DTOs, services, responses, events, listeners, and the Telegram approval webhook.

For step-by-step detail on individual flows, see the dedicated docs — this page stays at the module/route level and defers to them rather than duplicating their content:

- `docs/registration-workflow.md` — full registration walkthrough
- `docs/login-workflow.md` — full login walkthrough, including the lockout system
- `docs/notification-workflow.md` — how the admin-approval request reaches Telegram and the dashboard

## Team Structure Rule

Feature code belongs inside `app/Modules/{FeatureName}`.

```text
app/Modules/Auth/
  Controllers/
  Data/
  Events/
  Listeners/
  Requests/
  Responses/
  Services/

app/Modules/User/
  Controllers/
  Data/
  Policies/
  Requests/
  Services/
```

Shared application code stays global.

```text
app/Models
app/Enums
app/Providers
app/Helpers
app/Http/Middleware
```

Do not create global feature folders like `app/Data/Auth`, `app/Services/Auth`, or `app/Http/Requests/Auth`. New feature code should live inside its module.

## Layer Flow

Use this flow for web forms and APIs.

```text
Route
  -> Controller
  -> Request
  -> Data
  -> Service
  -> Response
```

- `Controller` coordinates the workflow.
- `Request` validates incoming input.
- `Data` contains DTOs with clean typed values.
- `Service` contains business logic.
- `Response` formats reusable JSON responses.
- Simple web redirects can return directly from controllers.

## Main Routes

Defined in `routes/web/backend/auth.php` unless noted otherwise.

- `GET /login` renders the login page for guests.
- `GET /instructor-register` renders the instructor signup page for guests (kept off `/register`, which is the separate frontend student-registration flow).
- `GET /code-verify` shows the OTP verification page (no guest restriction - reused by pending-status login too).
- `POST /login` calls `AuthController::loginWeb()` with `guest` + `throttle:login`.
- `POST /instructor-register` calls `AuthController::registerWeb()` with `guest` + `throttle:register`.
- `GET/POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password` handle the password-reset flow (`throttle:password-email` on the send step).
- `POST /api/code-verify` calls `AuthController::verifyCodeApi()` with `throttle:otp-verify`.
- `POST /logout` calls `AuthController::logoutWeb()` for authenticated users.
- `POST /api/telegram/webhook` (`routes/api.php`) is handled by `TelegramWebhookController` with `throttle:telegram-webhook`.

## Register Flow

Endpoint: `AuthController::registerWeb(RegisterWebRequest)`

Full walkthrough: `docs/registration-workflow.md`. Summary:

1. `RegisterWebRequest` validates `name`, `email` (unique, must match `@etec.com`), and `password` (min 8, confirmed).
2. Inside one DB transaction: creates the `User` (`role: instructor`, `status: pending`), syncs the Spatie `instructor` role, and creates an `InstructorData` row with a generated instructor code.
3. If OTP verification is enabled, creates an OTP via `OtpService::createForUser($user)`; otherwise auto-approves immediately (`source: otp_disabled`).
4. Logs the `user.registered` audit event.
5. If OTP is disabled, approves the user immediately, logs them in, regenerates the session, and redirects to `/dashboard`.
6. If OTP is enabled, stores `pending_verification_user_id` in the session, dispatches `PendingUserRegistered($user, $otp, $plainCode)`, and redirects to `/code-verify`.

### OTP Created During Registration

`OtpService::createForUser()`:

- Generates a random 6-digit code.
- Stores only the hashed code in `otp_verifications.otp_code` - the plain code is never persisted, only returned in memory.
- Sets `expires_at` to 10 minutes from creation, `attempts = 0`.
- Logs the `otp.created` audit event.
- Returns both the `OtpVerification` model and the plain code.

## Admin Notification Flow

Triggered by: `PendingUserRegistered`

Two listeners handle this event independently - a Telegram push and a dashboard notification row. Full detail, including how approvals from either channel converge on `UserApprovalService`, is in `docs/notification-workflow.md`. Summary:

- `SendTelegramAdminApproval` -> `TelegramService::sendAdminApprovalRequest()` sends the plain OTP code and user info to a configured Telegram admin chat, with inline `Approve`/`Reject` buttons (`callback_data: approve:{otp_id}` / `reject:{otp_id}`). No-ops silently if `telegram.admin_chat_id`/bot token aren't configured. Deduped per-OTP via a cache lock.
- `CreateAdminApprovalNotification` -> writes a `Notification` row (`type: instructor_approval`, linked via `otp_verification_id`) that the dashboard bell polls for (`GET /notifications/data`, every 20s) and lets `super_admin`/`admin` users approve/reject inline.

Important note:
- The plain OTP is only ever surfaced in these two admin-facing channels - it is never emailed or SMS'd to the registrant directly.

## Show Verification Page

Endpoint: `AuthController::showVerifyCode(Request)`

1. Reads `pending_verification_user_id` from the session.
2. If that session value is missing, it falls back to the authenticated user when the account is not active yet.
3. If a fallback user is used, the session key is restored.
4. If no pending user is found, redirects to `/instructor-register` with `Please register first to request a verification code.`
5. If the pending user has been rejected or deleted, redirects to `/instructor-register` with `Your registration was rejected. Please contact support.`
6. If the user status is `active`, redirects to `/login` with `Your account is already active.`
7. Otherwise renders `auth/VerifyCode` with:
   - `pendingEmail`
   - `pendingUserId`

## OTP Verification Flow

Endpoint: `AuthController::verifyCodeApi(VerifyCodeRequest)`

1. `VerifyCodeRequest` validates:
   - `code` is required and must be exactly 6 digits
   - optional `user_id` must exist in `users`
2. `VerifyCodeRequest::toData()` returns `VerifyCodeData`.
3. Resolves the pending user in this order:
   - `pending_verification_user_id` from session
   - authenticated user when status is not active
   - `userId` from the DTO
4. If no user is found, throws:
   - `Verification session has expired. Please register again.`
5. If the pending user is rejected or has already been deleted, redirects to `/instructor-register` with `Your registration was rejected. Please register again.`
6. If the user is already active, returns JSON through `VerificationResponse::alreadyActive()`:
   - `message = Account is already active.`
   - `redirect = permission-based path`
7. Calls `OtpService::verify($user, $data->code)`.
8. If OTP verification succeeds, calls `UserApprovalService::approve($user, null, 'otp')`.
9. Logs the user in, regenerates the session, clears `pending_verification_user_id`, and returns JSON through `VerificationResponse::verified()`:
   - `message = Account verified successfully.`
   - `redirect = permission-based path`

### `OtpService::verify()` Behavior

- Loads the latest unverified OTP for the user.
- Rejects when no OTP exists or the OTP is expired.
- Rejects when `attempts >= 5`.
- On invalid code:
  - increments `attempts`
  - logs `otp.failed`
  - returns `The verification code is invalid or has expired.`
- On success:
  - sets `verified_at = now()`
  - logs `otp.verified`

## Telegram Approval / Rejection Webhook

Endpoint: `POST /api/telegram/webhook`

This flow is separate from `AuthController`, but it belongs to the same activation process.

1. If `telegram.webhook_secret` is configured, the request must include the matching `X-Telegram-Bot-Api-Secret-Token` header.
   - The route also accepts the same secret in the webhook URL path, which matches Telegram's recommended "secret path" setup.
2. Reads the Telegram callback query data.
3. Accepts callback formats:
   - `approve:{otp_id}`
   - `reject:{otp_id}`
4. Loads the OTP with its related user.
5. If the callback action is invalid, Telegram receives `Invalid action.`
6. If the OTP or user is missing, Telegram receives `OTP not found.`
7. If the user is already rejected, Telegram receives `User already rejected.`
8. If action is `approve`:
   - calls `UserApprovalService::approve($user, null, 'telegram')`
   - responds to Telegram with `User approved.`
   - logs `telegram.approved` with `otp_id`
9. If action is `reject`:
   - calls `UserApprovalService::reject($user, null, 'telegram')`
   - responds to Telegram with `User rejected.`
   - logs `telegram.rejected` with `otp_id`

Important note:
- Telegram approval can activate the account before the user manually enters the OTP.
- `UserApprovalService::approve()` also marks the latest unverified OTP as verified.
- If the user later submits the OTP after Telegram approval already activated the account, `verifyCodeApi()` returns `Account is already active.`

## Login Flow

Endpoint: `AuthController::loginWeb(LoginWebRequest)`

Full walkthrough, including the two-layer lockout system, is in `docs/login-workflow.md`. Summary:

1. `LoginWebRequest` validates `login` or `email`, plus `password`.
2. Checks `LoginLockoutService::isBanned()` first - account-wide, keyed on the login alone (not IP), so rotating IPs doesn't help a banned account.
3. Pre-first-offense, a short login+IP `RateLimiter` burst applies; once an account has ever tripped a lockout, every wrong attempt escalates the ban immediately instead.
4. `AuthService::findUserForLogin()` looks up by `email` or `name`. `Hash::check()` always runs (against a dummy hash if no user was found), so a nonexistent login takes the same time to reject as a wrong password - no timing leak.
5. Wrong credentials: registers a lockout failure (escalating ban tiers, then a hard block past the last tier) and returns the generic message `These credentials do not match our records.` The real reason is audit-logged only, never shown to the client.
6. Only after a correct password does status get checked: `Inactive`/`Rejected` throw their own errors; any other non-`Active` status (i.e. `pending`) redirects to `/code-verify` instead of throwing.
7. On success: clears the rate limiter and lockout history, `Auth::login()` with optional `remember`, regenerates the session, and redirects to the permission-based path (`redirectPathFor()`) with `Logged in successfully.`

## Forgot / Reset Password Flow

Endpoints: `AuthController::sendResetLink(ForgotPasswordRequest)`, `AuthController::resetPassword(ResetPasswordRequest)`

1. `sendResetLink()` calls Laravel's `Password::sendResetLink()`. The response is the same generic success message whether or not the email exists, so the endpoint can't be used to enumerate accounts.
2. `resetPassword()` calls `Password::reset()`, force-fills the new password, then clears the account's login-lockout history and deletes every other active session for that user (`sessions` table), so a password reset also kills any hijacked sessions.
3. If the user has a verified recovery email, sends `PasswordChangedNotification` there (not to the login email, in case that inbox is the compromised one).

## Logout Flow

Endpoint: `AuthController::logoutWeb(Request)`

1. Logs the user out.
2. Invalidates the session.
3. Regenerates the CSRF token.
4. Redirects to `/login`.

## Post-Login Redirect

Method: `redirectPathFor(User)`

Permission-based, not role-name-based: `/dashboard` if the user `can('dashboard.view')`, otherwise `/login`.

## Adding New Module Code

For a new feature, start with this structure.

```text
app/Modules/Course/
  Controllers/
  Data/
  Requests/
  Responses/
  Services/
```

Routes should import controllers from the module namespace.

```php
use App\Modules\Course\Controllers\CourseController;
```
