# AuthController Workflow

This document summarizes the request flows implemented in AuthController.

## Register (Web)
- Endpoint: registerWeb(Request)
- Validates name, @etec.com email, and password confirmation.
- Creates user (inactive), assigns default role, creates verification code, and logs a notification.
- Logs the user in, stores pending verification user ID in session, and redirects to /code-verify.

## Show Verification Code Page
- Endpoint: showVerifyCode(Request)
- Uses pending verification user ID from session or the authenticated inactive user.
- If no pending user, redirects to /register with an error.
- Renders auth/VerifyCode with pending email and user ID.

## Verify Code (API)
- Endpoint: verifyCodeApi(Request)
- Validates 6-digit code and optional user_id.
- Resolves pending user from session, authenticated inactive user, or provided user_id.
- If no user is found, returns validation error.
- Looks up latest unverified, unexpired code for the user.
- If invalid or expired, returns validation error.
- Marks code verified, activates user, sets email_verified_at.
- Logs in user, regenerates session, clears pending verification session.
- Returns JSON with redirect path.

## Login (Web)
- Endpoint: loginWeb(Request)
- Accepts login or email + password.
- Finds user, verifies password.
- If inactive, returns validation error requesting verification.
- Logs in user, regenerates session, redirects to role-based path.

## Logout (Web)
- Endpoint: logoutWeb(Request)
- Logs out user, invalidates session, regenerates CSRF token.
- Redirects to /login.

## Role-Based Redirect
- Method: redirectPathFor(User)
- Roles: super_admin, admin, instructor => /dashboard
- Other roles => /
