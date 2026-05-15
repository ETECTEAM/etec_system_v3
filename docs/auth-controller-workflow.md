# AuthController Workflow

This document summarizes the request flows implemented in AuthController.

## Register (Web)
- Endpoint: registerWeb(Request)
- Validates name, @etec.com email, and password confirmation.
- Creates user with status pending and is_active false.
- Assigns default role, creates OTP verification, and emits a Telegram approval event.
- Logs the user in, stores pending verification user ID in session, and redirects to /code-verify.

## Show Verification Code Page
- Endpoint: showVerifyCode(Request)
- Uses pending verification user ID from session or the authenticated user.
- If no pending user, redirects to /register with an error.
- If user is active, redirects to /login.
- If user is rejected, redirects to /register.
- Renders auth/VerifyCode with pending email and user ID.

## Verify Code (API)
- Endpoint: verifyCodeApi(Request)
- Validates 6-digit code and optional user_id.
- Resolves pending user from session, authenticated pending user, or provided user_id.
- If no user is found, returns validation error.
- If user is rejected, returns validation error.
- Verifies OTP (max attempts, expiry check) and activates the account.
- Logs in user, regenerates session, clears pending verification session.
- Returns JSON with redirect path.

## Login (Web)
- Endpoint: loginWeb(Request)
- Accepts login or email + password.
- Finds user, verifies password.
- If rejected, returns validation error.
- If not active, returns validation error requesting verification.
- Logs in user, regenerates session, redirects to role-based path.

## Logout (Web)
- Endpoint: logoutWeb(Request)
- Logs out user, invalidates session, regenerates CSRF token.
- Redirects to /login.

## Role-Based Redirect
- Method: redirectPathFor(User)
- Roles: super_admin, admin, instructor => /dashboard
- Other roles => /
