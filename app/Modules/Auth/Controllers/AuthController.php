<?php

namespace App\Modules\Auth\Controllers;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\InstructorData;
use App\Models\User;
use App\Modules\Auth\Events\PendingUserRegistered;
use App\Modules\Auth\Notifications\PasswordChangedNotification;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\LoginWebRequest;
use App\Modules\Auth\Requests\RegisterWebRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\VerifyCodeRequest;
use App\Modules\Auth\Responses\VerificationResponse;
use App\Modules\Auth\Services\AuthAuditService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\LoginLockoutService;
use App\Modules\Auth\Services\OtpService;
use App\Modules\Auth\Services\TokenExpirationService;
use App\Modules\Instructor\Services\InstructorService;
use App\Modules\Instructor\Services\InstructorOnboardingService;
use App\Modules\User\Services\UserApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Coordinates web authentication, registration, OTP verification, and logout.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly OtpService $otpService,
        private readonly UserApprovalService $approvalService,
        private readonly AuthAuditService $auditService,
        private readonly LoginLockoutService $lockoutService,
        private readonly InstructorOnboardingService $onboarding,
    ) {}

    public function showLogin(): Response
    {
        return Inertia::render('auth/Login');
    }

    // Instructor signup lives here, not /register (the frontend student flow).
    public function showRegister(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function registerWeb(RegisterWebRequest $request): RedirectResponse
    {
        $data = $request->toData();
        $otpVerificationEnabled = (bool) config('auth.otp.enabled', true);

        // User, role, and OTP must be created together or rolled back together.
        [$user, $otp, $plainCode] = DB::transaction(function () use ($data, $otpVerificationEnabled): array {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'role' => 'instructor',
                // Role-based access window is minted at the first login event
                // (OTP-disabled login, OTP activation, or normal login), not here.
                'status' => 'pending',
                // Self-registered instructors must complete setup (profile
                // details, work schedule, specialization, verified recovery
                // email) before they can use the dashboard.
                'requires_onboarding' => true,
            ]);

            Role::findOrCreate('instructor', 'web');
            $user->syncRoles(['instructor']);

            InstructorData::create([
                'user_id' => $user->id,
                'full_name' => $data->name,
                'instructor_code' => InstructorService::generateInstructorCode(),
            ]);

            if (! $otpVerificationEnabled) {
                return [$user, null, null];
            }

            [$otp, $plainCode] = $this->otpService->createForUser($user);

            return [$user, $otp, $plainCode];
        });

        $this->auditService->log($user, 'user.registered', $request->ip());

        if (! $otpVerificationEnabled) {
            $this->approvalService->approve($user, null, 'otp_disabled');
            $this->applyAccessExpiration($user, $request->ip(), $data->email);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect($this->redirectPathFor($user))->with('success', 'Registration completed. OTP verification is disabled.');
        }

        $request->session()->put('pending_verification_user_id', $user->id);

        try {
            PendingUserRegistered::dispatch($user, $otp, $plainCode, $request->ip());
        } catch (Throwable $e) {
            // Registration must still succeed even if a notification listener or
            // Telegram delivery path fails.
            report($e);
        }

        return redirect('/code-verify')
            ->with('success', 'Registration received. Enter your verification code to activate your account.');
    }

    // Reused by both registration and pending-status login, so no guest restriction.
    public function showVerifyCode(Request $request): Response|RedirectResponse
    {
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        // Session expired but still logged in as the pending user - rebuild it.
        if (! $user && $request->user() && $request->user()->status !== UserStatus::Active) {
            $user = $request->user();
            $request->session()->put('pending_verification_user_id', $user->id);
        }

        if (! $user) {
            return redirect('/instructor-register')->with('error', 'Please register first to request a verification code.');
        }

        if ($user->status === UserStatus::Rejected) {
            return redirect('/instructor-register')->with('error', 'Your registration was rejected. Please contact support.');
        }

        if ($user->status === UserStatus::Active) {
            return redirect('/login')->with('success', 'Your account is already active.');
        }

        return Inertia::render('auth/VerifyCode', [
            'pendingEmail' => $user->email,
            'pendingUserId' => $user->id,
        ]);
    }

    // Rate-limited to slow code-guessing.
    public function verifyCodeApi(VerifyCodeRequest $request): JsonResponse
    {
        $data = $request->toData();

        // Prefer session state, then authenticated user, then explicit user id.
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $authenticatedUser = $request->user();
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        if (! $user && $authenticatedUser && $authenticatedUser->status !== UserStatus::Active) {
            $user = $authenticatedUser;
            $pendingUserId = $authenticatedUser->id;
        }

        if (! $user && $data->userId !== null) {
            $user = User::query()->find($data->userId);
            $pendingUserId = $user?->id;
        }

        if (! $user) {
            return VerificationResponse::rejected('/instructor-register');
        }

        if ($user->status === UserStatus::Rejected) {
            return VerificationResponse::rejected('/instructor-register');
        }

        if ($user->status === UserStatus::Active) {
            return VerificationResponse::alreadyActive($this->redirectPathFor($user));
        }

        $this->otpService->verify($user, $data->code);
        $this->approvalService->approve($user, null, 'otp');
        $this->applyAccessExpiration($user, $request->ip());

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('pending_verification_user_id');

        return VerificationResponse::verified($this->postVerificationRedirect($user));
    }

    /**
     * Where a freshly verified user lands. Newly self-registered instructors
     * who still need to finish setup are sent into the onboarding wizard
     * instead of straight to the dashboard; everyone else goes to the normal
     * redirect.
     */
    private function postVerificationRedirect(User $user): string
    {
        if ($this->onboarding->isPending($user)) {
            return '/dashboard/instructor/onboarding';
        }

        return $this->redirectPathFor($user);
    }

    public function loginWeb(LoginWebRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->toData();
        $loginKey = Str::lower($data->login); // convert email uppercase to lowercase
        // dd($loginKey);

        // Account-wide ban, independent of IP - checked first so a banned
        // account can't burn through attempts from a new IP.
        if ($this->lockoutService->isBanned($loginKey)) {
            return $this->bannedResponse($request, $loginKey);
        }

        // Once an account has ever tripped a lockout, every wrong attempt
        // escalates immediately (iPhone-style); only the first-ever lockout
        // needs a burst of free attempts. See hasOffenseHistory().
        $hasOffenseHistory = $this->lockoutService->hasOffenseHistory($loginKey);

        // login+IP bucket only matters pre-first-lockout; once the account-wide
        // ban is active, rotating IPs no longer helps an attacker.
        $limiterKey = $loginKey.'|'.$request->ip();

        if (! $hasOffenseHistory) {
            $freeAttempts = $this->lockoutService->freeAttempts();

            if (RateLimiter::tooManyAttempts($limiterKey, $freeAttempts)) {
                $seconds = RateLimiter::availableIn($limiterKey);

                throw ValidationException::withMessages([
                    'login' => ["Too many login attempts. Please try again in {$seconds} seconds."],
                ]);
            }
        }

        $user = $this->authService->findUserForLogin($data->login);

        // Hash::check() always runs, against a dummy hash if no user, so a
        // nonexistent login takes as long as a real one (no timing leak).
        $dummyHash = config('auth.dummy_password_hash');
        $passwordMatches = Hash::check($data->password, $user?->password ?? $dummyHash);

        if (! $user || ! $passwordMatches) {
            if ($hasOffenseHistory) {
                $this->lockoutService->registerFailure($loginKey);
            } else {
                RateLimiter::hit($limiterKey, 60);

                if (RateLimiter::attempts($limiterKey) >= $this->lockoutService->freeAttempts()) {
                    $this->lockoutService->registerFailure($loginKey);
                }
            }

            // Generic message for both cases; real reason only goes to the audit log.
            $this->auditService->log($user, 'login.failed', $request->ip(), [
                'reason' => $user ? 'invalid_password' : 'email_not_found',
                'login' => $data->login,
            ]);

            if ($this->lockoutService->isBanned($loginKey)) {
                return $this->bannedResponse($request, $loginKey);
            }

            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        // Status only revealed after password check, so it can't be probed without valid credentials.
        if ($user->status === UserStatus::Inactive) {
            throw ValidationException::withMessages(['login' => ['Your account is inactive. Please contact administrator.']]);
        }

        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages(['login' => ['Your registration was rejected. Please contact support.']]);
        }

        // Non-active (e.g. pending) still needs OTP/approval instead of logging in.
        if ($user->status !== UserStatus::Active) {
            $request->session()->put('pending_verification_user_id', $user->id);

            return redirect('/code-verify')
                ->with('success', 'Please verify your account before logging in.');
        }
        //
        // Mint the role-based token/session expiry from this login's issued
        // time: instructor +1 month, admin / super_admin +1 year (see
        // config('auth.token_expiration.roles')). Roles with no configured
        // lifetime (e.g. student) leave the access columns untouched and never
        // expire. The window clock starts/freshly restarts at every successful
        // login, so an expired account simply logs in again to renew.
        $this->applyAccessExpiration($user, $request->ip(), $data->login);

        Auth::login($user, $data->remember);
        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();

        // No success flash: landing on the dashboard is confirmation enough, and
        // the global toast host would otherwise pop "Logged in successfully" on
        // every sign-in. Create/edit/delete flashes are unaffected.
        return redirect($this->redirectPathFor($user));
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('auth/ForgotPassword');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $data = $request->toData();
        $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($data->email)])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ["This email doesn't have an account in the system."],
            ]);
        }

        if (! $user->passwordResetRecipient()) {
            throw ValidationException::withMessages([
                'email' => ['This account does not have a recovery email yet.'],
            ]);
        }

        $status = Password::sendResetLink(['email' => $data->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect('/forgot-password')
            ->with('success', 'Password reset link sent to your recovery email.');
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->toData();
        $resetUser = null;

        $status = Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'password_confirmation' => $data->passwordConfirmation,
                'token' => $data->token,
            ],
            function (User $user) use ($data, &$resetUser): void {
                $user->forceFill(['password' => $data->password])->save();
                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        // Reaching this required a verified recovery email, so clear lockout
        // history and kill every other session for this account.
        $this->lockoutService->clear(Str::lower($data->email));
        DB::table('sessions')->where('user_id', $resetUser->id)->delete();

        if ($recipient = $resetUser->passwordResetRecipient()) {
            Notification::route('mail', $recipient)
                ->notify(new PasswordChangedNotification($resetUser->email));
        }

        return redirect('/login')->with('success', 'Your password has been reset. Please sign in.');
    }

    public function logoutWeb(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function redirectPathFor(User $user): string
    {
        if ($user->can('dashboard.view')) {
            return '/dashboard';
        }

        return '/login';
    }

    // Mints the role-based token/session expiry on every successful login
    // event: normal login, OTP-activation login, and OTP-disabled registration
    // login. The window always starts at the current login's issued time.
    private function applyAccessExpiration(User $user, ?string $ipAddress = null, ?string $login = null): void
    {
        $expiresAt = app(TokenExpirationService::class)->expiresAt($user);

        // Roles without a configured lifetime (e.g. student) keep the existing
        // never-expire behavior - the access columns stay as they are.
        if ($expiresAt === null) {
            return;
        }

        $action = $user->access_expires_at === null ? 'login.window_started' : 'login.access_renewed';

        $user->forceFill([
            'access_renewed_at' => now(),
            'access_expires_at' => $expiresAt,
        ])->save();

        $this->auditService->log($user, $action, $ipAddress, $login !== null ? ['login' => $login] : []);
    }

    // Shared by the upfront isBanned() check and the failure branch that just
    // tripped it, so both report the same message immediately.
    private function bannedResponse(Request $request, string $loginKey): RedirectResponse|JsonResponse
    {
        $seconds = $this->lockoutService->secondsRemaining($loginKey);

        // Past the last tier the account is hard-blocked, not timed.
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
}
