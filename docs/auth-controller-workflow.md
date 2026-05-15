# AuthController Workflow

This document reflects the current authentication flow implemented across `AuthController`, `OtpService`, `UserApprovalService`, and the Telegram approval webhook.

## Main Routes

- `GET /login` renders the login page for guests.
- `GET /register` renders the register page for guests.
- `GET /code-verify` shows the OTP verification page.
- `POST /login` calls `AuthController::loginWeb()` with `throttle:login`.
- `POST /register` calls `AuthController::registerWeb()` with `throttle:register`.
- `POST /api/code-verify` calls `AuthController::verifyCodeApi()` with `throttle:otp-verify`.
- `POST /logout` calls `AuthController::logoutWeb()` for authenticated users.
- `POST /api/telegram/webhook` is handled by `TelegramWebhookController` with `throttle:telegram-webhook`.

## Register Flow

Endpoint: `registerWeb(Request)`

1. Validates:
   - `name` is required
   - `email` is required, unique, valid, and must match `@etec.com`
   - `password` is required, minimum 8 characters, and must be confirmed
2. Creates the user in a database transaction with:
   - `status = pending`
   - `is_active = false`
3. Ensures the default role exists and assigns it to the user.
4. Creates a new OTP with `OtpService::createForUser($user)`.
5. Logs the `user.registered` audit event.
6. Logs the new user in immediately and regenerates the session.
7. Stores `pending_verification_user_id` in session.
8. Dispatches `PendingUserRegistered($user, $otp, $plainCode)`.
9. Redirects to `/code-verify` with a success message.

### OTP Created During Registration

`OtpService::createForUser()` currently:

- Generates a random 6-digit code.
- Stores only the hashed OTP in `otp_verifications.otp_code`.
- Sets `expires_at` to 10 minutes from creation.
- Starts with `attempts = 0`.
- Logs the `otp.created` audit event.
- Returns both the `OtpVerification` model and the plain OTP code.

## Telegram Notification Flow

Triggered by: `PendingUserRegistered`

The registration event listener calls `TelegramService::sendAdminApprovalRequest($user, $otp, $plainCode)`.

Telegram message behavior:

- Sends the message only when `telegram.admin_chat_id` is configured.
- Uses a cache lock key `telegram:approval-request:{otp_id}` to avoid duplicate sends for the same OTP.
- Includes:
  - registration title: `New Instructor Registration`
  - user name
  - user email
  - `Phone: not provided`
  - plain OTP code
  - approval prompt text
- Adds inline buttons with callback data:
  - `approve:{otp_id}`
  - `reject:{otp_id}`
- Removes the cache lock if Telegram sending throws an exception, so the request can be retried.

Important note:
- The plain OTP is sent to Telegram admins, but only the hashed OTP is stored in the database.

## Show Verification Page

Endpoint: `showVerifyCode(Request)`

1. Reads `pending_verification_user_id` from the session.
2. If that session value is missing, it falls back to the authenticated user when the account is not active yet.
3. If a fallback user is used, the session key is restored.
4. If no pending user is found, redirects to `/register` with `Please register first to request a verification code.`
5. If the user status is `rejected`, redirects to `/register` with `Your registration was rejected. Please contact support.`
6. If the user status is `active`, redirects to `/login` with `Your account is already active.`
7. Otherwise renders `auth/VerifyCode` with:
   - `pendingEmail`
   - `pendingUserId`

## OTP Verification Flow

Endpoint: `verifyCodeApi(Request)`

1. Validates:
   - `code` is required and must be exactly 6 digits
   - optional `user_id` must exist in `users`
2. Resolves the pending user in this order:
   - `pending_verification_user_id` from session
   - authenticated user when status is not active
   - `user_id` from the request
3. If no user is found, throws:
   - `Verification session has expired. Please register again.`
4. If the user is rejected, throws:
   - `Your registration was rejected. Please contact support.`
5. If the user is already active, returns JSON:
   - `message = Account is already active.`
   - `redirect = role-based path`
6. Calls `OtpService::verify($user, $validated['code'])`.
7. If OTP verification succeeds, calls `UserApprovalService::approve($user, null, 'otp')`.
8. Logs the user in, regenerates the session, clears `pending_verification_user_id`, and returns:
   - `message = Account verified successfully.`
   - `redirect = role-based path`

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

Endpoint: `loginWeb(Request)`

1. Validates:
   - `login` or `email`
   - `password`
2. Resolves the login identifier from `login` first, then `email`.
3. `AuthService::findUserForLogin()` searches:
   - by `email` when the input looks like an email
   - by `name` otherwise
4. If the user is missing or the password is wrong, throws:
   - `The provided credentials are incorrect.`
5. If the user is rejected, throws:
   - `Your account was rejected. Please contact support.`
6. If the user is not active, throws:
   - `Your account is pending verification. Please complete the 6-digit verification first.`
7. If valid, logs the user in with optional `remember`, regenerates the session, and redirects to the role-based path with `Logged in successfully.`

## Logout Flow

Endpoint: `logoutWeb(Request)`

1. Logs the user out.
2. Invalidates the session.
3. Regenerates the CSRF token.
4. Redirects to `/login`.

## Role-Based Redirect

Method: `redirectPathFor(User)`

- `super_admin` => `/dashboard`
- `admin` => `/dashboard`
- `instructor` => `/dashboard`
- any other role => `/`
