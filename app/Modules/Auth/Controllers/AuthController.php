<?php

namespace App\Modules\Auth\Controllers;

// Domain enums and app models referenced across the auth flow.
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\InstructorData;
use App\Models\User;

// Auth module events, requests, responses, and services.
use App\Modules\Auth\Events\PendingUserRegistered;
use App\Modules\Instructor\Services\InstructorService;
use App\Modules\User\Services\UserService;
use App\Modules\Auth\Requests\LoginWebRequest;
use App\Modules\Auth\Requests\RegisterWebRequest;
use App\Modules\Auth\Requests\VerifyCodeRequest;
use App\Modules\Auth\Responses\VerificationResponse;
use App\Modules\Auth\Services\AuthAuditService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\LoginLockoutService;
use App\Modules\Auth\Services\OtpService;
use App\Modules\User\Services\UserApprovalService;

// Framework/Laravel core: HTTP, auth guard, hashing, rate limiting, validation.
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Coordinates web authentication, registration, OTP verification, and logout.
 */
class AuthController extends Controller
{
    // Inject the services this controller delegates business logic to.
    public function __construct(
        private readonly AuthService $authService,
        private readonly OtpService $otpService,
        private readonly UserApprovalService $approvalService,
        private readonly AuthAuditService $auditService,
        private readonly LoginLockoutService $lockoutService,
    ) {}

    // Route to display the login page for guest users.
    public function showLogin(): Response
    {
        // Render the login form.
        return Inertia::render('auth/Login');
    }

    // Route to display the instructor registration screen for guests (kept off /register, which is the frontend student flow).
    public function showRegister(): Response
    {
        // Render the instructor registration form.
        return Inertia::render('auth/Register');
    }

    // Route to process instructor registration submissions; guest-only, with the 'register' rate limiter to slow signup abuse.
    public function registerWeb(RegisterWebRequest $request): RedirectResponse
    {
        // Convert the validated request into a typed DTO.
        $data = $request->toData();
        // Check whether OTP verification is turned on for this environment.
        $otpVerificationEnabled = (bool) config('auth.otp.enabled', true);

        // User, role, and OTP must be created together or rolled back together.
        [$user, $otp, $plainCode] = DB::transaction(function () use ($data, $otpVerificationEnabled): array {
            // Create the new instructor account with a pending status.
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'role' => 'instructor',
                'status' => 'pending',
            ]);

            // Create instructor role if not exists.
            Role::findOrCreate('instructor', 'web');

            // Assign Spatie role instructor.
            $user->syncRoles(['instructor']);

            // Store additional instructor profile data linked to the new user.
            InstructorData::create([
                'user_id' => $user->id,
                'full_name' => $data->name,
                'instructor_code' => InstructorService::generateInstructorCode(),
            ]);

            // Skip OTP issuance when verification is disabled.
            if (! $otpVerificationEnabled) {
                return [$user, null, null];
            }

            // Generate and persist an OTP for the new user.
            [$otp, $plainCode] = $this->otpService->createForUser($user);

            return [$user, $otp, $plainCode];
        });

        // Record the registration event in the audit log.
        $this->auditService->log($user, 'user.registered', $request->ip());

        // Log the new user in immediately; status gates access until verified.
        Auth::login($user);
        // Regenerate the session ID to prevent session fixation.
        $request->session()->regenerate();

        // When OTP is disabled, approve the account immediately.
        if (! $otpVerificationEnabled) {
            $this->approvalService->approve($user, null, 'otp_disabled');

            // Send the user straight to their role-appropriate landing page.
            return redirect($this->redirectPathFor($user))->with('success', 'Registration completed. OTP verification is disabled.');
        }

        // Track this user as pending OTP verification.
        $request->session()->put('pending_verification_user_id', $user->id);

        // Dispatch event to send the OTP code to the user.
        PendingUserRegistered::dispatch($user, $otp, $plainCode);

        // Send the user to the verification page to enter their OTP.
        return redirect('/code-verify')
            ->with('success', 'Registration received. Enter your verification code to activate your account.');
    }

    // Route to display the OTP verification page; reused by both registration and pending-status login, so no guest restriction here.
    public function showVerifyCode(Request $request): Response|RedirectResponse
    {
        // Look up the pending verification user from the session.
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        // If the session expired but the pending user is logged in, rebuild it.
        if (! $user && $request->user() && $request->user()->status !== UserStatus::Active) {
            $user = $request->user();
            $request->session()->put('pending_verification_user_id', $user->id);
        }

        // No pending session and no user context; send back to register.
        if (! $user) {
            return redirect('/instructor-register')->with('error', 'Please register first to request a verification code.');
        }

        // Rejected accounts can't verify; send back to register.
        if ($user->status === UserStatus::Rejected) {
            return redirect('/instructor-register')->with('error', 'Your registration was rejected. Please contact support.');
        }

        // Already verified; nothing to do here but go to login.
        if ($user->status === UserStatus::Active) {
            return redirect('/login')->with('success', 'Your account is already active.');
        }

        // Render the verification form for the pending user.
        return Inertia::render('auth/VerifyCode', [
            'pendingEmail' => $user->email,
            'pendingUserId' => $user->id,
        ]);
    }

    // Route to validate a submitted OTP code and activate the pending account; rate-limited to slow code-guessing.
    public function verifyCodeApi(VerifyCodeRequest $request): JsonResponse
    {
        // Convert the validated request into a typed DTO.
        $data = $request->toData();

        // Prefer session state, then authenticated user, then explicit user id.
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $authenticatedUser = $request->user();
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        // Fall back to the currently authenticated pending user.
        if (! $user && $authenticatedUser && $authenticatedUser->status !== UserStatus::Active) {
            $user = $authenticatedUser;
            $pendingUserId = $authenticatedUser->id;
        }

        // Fall back to an explicit user id passed by the client.
        if (! $user && $data->userId !== null) {
            $user = User::query()->find($data->userId);
            $pendingUserId = $user?->id;
        }

        // No way to resolve the pending user; the session has expired.
        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['Verification session has expired. Please register again.'],
            ]);
        }

        // Rejected accounts can't verify.
        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages([
                'code' => ['Your registration was rejected. Please contact support.'],
            ]);
        }

        // Already verified; report success without re-verifying.
        if ($user->status === UserStatus::Active) {
            return VerificationResponse::alreadyActive($this->redirectPathFor($user));
        }

        // Verify the submitted OTP code matches the pending record.
        $this->otpService->verify($user, $data->code);
        // Mark the account approved now that OTP is verified.
        $this->approvalService->approve($user, null, 'otp');

        // Log the user in now that verification is complete.
        Auth::login($user);
        // Regenerate the session ID to prevent session fixation.
        $request->session()->regenerate();

        // Clear the pending-verification marker now that it's resolved.
        $request->session()->forget('pending_verification_user_id');

        // Respond with the redirect target for the now-active user.
        return VerificationResponse::verified($this->redirectPathFor($user));
    }

    // Route to process login form submissions; guest-only, with the 'login' rate limiter to slow brute-force attempts.
    public function loginWeb(LoginWebRequest $request): RedirectResponse|JsonResponse
    {
        // Convert the validated request into a typed DTO.
        $data = $request->toData();
        $loginKey = Str::lower($data->login);

        // Account-wide escalating ban, independent of IP - see
        // LoginLockoutService. Checked before the short-window limiter below
        // so a banned account can't burn through its attempts from a new IP.
        if ($this->lockoutService->isBanned($loginKey)) {
            $seconds = $this->lockoutService->secondsRemaining($loginKey);

            // Past the last configured tier the account is hard-blocked
            // rather than timed - point the user at an admin instead of a
            // countdown, since only an admin (or the full reset window) lifts it.
            $isHardBlock = $this->lockoutService->isHardBlocked($loginKey);
            $message = $isHardBlock
                ? 'Your account has been blocked due to repeated failed login attempts. Contact an administrator or wait for it to reset automatically.'
                : "Too many failed login attempts. Please try again in {$seconds} seconds.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'retry_after' => $seconds,
                    'is_hard_block' => $isHardBlock,
                ], 429, ['Retry-After' => (string) $seconds]);
            }

            return back()
                ->withErrors(['login' => $message])
                ->with(['error' => $message, 'retryAfter' => $seconds, 'isHardBlock' => $isHardBlock])
                ->header('Retry-After', (string) $seconds);
        }

        // Combine login + IP so one attacker IP can't lock out a shared account,
        // and one leaked account can't be used to lock out other IPs.
        // NOTE: this short window only throttles a single IP's burst of
        // attempts - an attacker rotating IPs still gets a fresh 5-attempt
        // bucket per IP here. What actually stops them is the account-wide
        // ban above: crossing this threshold escalates a ban keyed on the
        // account alone (see registerFailure() below), so switching IPs no
        // longer resets anything once that ban is in effect.
        $limiterKey = $loginKey.'|'.$request->ip();

        // Block further attempts once this login+IP pair is locked out.
        // NOTE: the account owner isn't notified when this - or the
        // account-wide ban above - trips; only the caller sees the error.
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            throw ValidationException::withMessages([
                'login' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        // Look up the user by email or username.
        $user = $this->authService->findUserForLogin($data->login);

        // Always run Hash::check(), against a dummy hash when the user doesn't
        // exist, so a nonexistent login takes as long as a real one (no timing
        // side-channel for account enumeration).
        $dummyHash = config('auth.dummy_password_hash');
        $passwordMatches = Hash::check($data->password, $user?->password ?? $dummyHash);

        // Wrong password and unknown user are treated identically from here on.
        if (! $user || ! $passwordMatches) {
            // Count this attempt against the login+IP limiter.
            RateLimiter::hit($limiterKey, 60);

            // This attempt just exhausted the short window - escalate an
            // account-wide ban via LoginLockoutService (see the NOTE above).
            if (RateLimiter::attempts($limiterKey) >= 5) {
                $this->lockoutService->registerFailure($loginKey);
            }

            // One generic client-facing message for both cases; the real reason
            // is only recorded here, in the audit log, to avoid enumeration.
            $this->auditService->log($user, 'login.failed', $request->ip(), [
                'reason' => $user ? 'invalid_password' : 'email_not_found',
                'login' => $data->login,
            ]);

            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        // Status is only revealed after the password has been verified, so an
        // attacker without valid credentials can't probe account state.
        if ($user->status === UserStatus::Inactive) {
            throw ValidationException::withMessages(['login' => ['Your account is inactive. Please contact administrator.']]);
        }

        // Rejected accounts are blocked outright.
        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages(['login' => ['Your registration was rejected. Please contact support.']]);
        }

        // Any other non-active status (e.g. pending) still needs OTP/approval,
        // so redirect into the same verification flow registerWeb uses instead
        // of logging the user in.
        // NOTE: OTP here only gates account activation, not every login — an
        // Active account logs in with password alone, no per-login 2FA.
        if ($user->status !== UserStatus::Active) {
            $request->session()->put('pending_verification_user_id', $user->id);

            return redirect('/code-verify')
                ->with('success', 'Please verify your account before logging in.');
        }

        // Successful login: forget prior failed attempts for this login+IP pair,
        // and reset the account's escalation history so the next lockout (if
        // any) starts back at the first tier.
        RateLimiter::clear($limiterKey);
        $this->lockoutService->clear($loginKey);

        // Log the verified, active user in.
        // NOTE: unlike the failure path above, successful logins aren't sent to
        // AuthAuditService — there's currently no audit trail of who logged in
        // when, only of failures and registrations.
        Auth::login($user, $data->remember);
        // Record the last login timestamp.
        $user->forceFill(['last_login_at' => now()])->save();
        // Regenerate the session ID to prevent session fixation.
        $request->session()->regenerate();

        // Send the user to their role-appropriate landing page.
        return redirect($this->redirectPathFor($user))
            ->with('success', 'Logged in successfully.');
    }

    // Route to log the authenticated user out and invalidate their session.
    public function logoutWeb(Request $request): RedirectResponse
    {
        // Log the user out of the guard.
        Auth::logout();
        // Invalidate the session data.
        $request->session()->invalidate();
        // Rotate the CSRF token.
        $request->session()->regenerateToken();

        // Send the user back to the login page.
        return redirect('/login');
    }

    // Pick the landing page for a logged-in user based on their permissions.
    private function redirectPathFor(User $user): string
    {
        // Users with dashboard access land on the dashboard.
        if ($user->can('dashboard.view')) {
            return '/dashboard';
        }

        // Everyone else falls back to the login page.
        return '/login';
    }
}
