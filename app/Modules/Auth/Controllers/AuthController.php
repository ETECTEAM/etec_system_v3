<?php

namespace App\Modules\Auth\Controllers;

use App\Enums\UserStatus;
use App\Modules\Auth\Events\PendingUserRegistered;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginWebRequest;
use App\Modules\Auth\Requests\RegisterWebRequest;
use App\Modules\Auth\Requests\VerifyCodeRequest;
use App\Modules\Auth\Responses\VerificationResponse;
use App\Models\User;
use App\Modules\Auth\Services\AuthAuditService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\OtpService;
use App\Modules\User\Services\UserApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
    ) {}

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
                'is_active' => false,
                'status' => UserStatus::Pending,
            ]);

            $role = $this->authService->ensureDefaultRole();

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            if (! $otpVerificationEnabled) {
                return [$user, null, null];
            }

            [$otp, $plainCode] = $this->otpService->createForUser($user);

            return [$user, $otp, $plainCode];
        });

        $this->auditService->log($user, 'user.registered', $request->ip());

        Auth::login($user);
        $request->session()->regenerate();

        if (! $otpVerificationEnabled) {
            $this->approvalService->approve($user, null, 'otp_disabled');

            return redirect($this->redirectPathFor($user))->with('success', 'Registration completed. OTP verification is disabled.');
        }

        $request->session()->put('pending_verification_user_id', $user->id);

        // Notify admins after the user and OTP exist.
        PendingUserRegistered::dispatch($user, $otp, $plainCode);

        return redirect('/code-verify')->with('success', 'Registration received. Enter your verification code to activate your account.');
    }

    public function showVerifyCode(Request $request): Response|RedirectResponse
    {
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        // If the session expired but the pending user is logged in, rebuild it.
        if (! $user && $request->user() && $request->user()->status !== UserStatus::Active) {
            $user = $request->user();
            $request->session()->put('pending_verification_user_id', $user->id);
        }

        if (! $user) {
            return redirect('/register')->with('error', 'Please register first to request a verification code.');
        }

        if ($user->status === UserStatus::Rejected) {
            return redirect('/register')->with('error', 'Your registration was rejected. Please contact support.');
        }

        if ($user->status === UserStatus::Active) {
            return redirect('/login')->with('success', 'Your account is already active.');
        }

        return Inertia::render('auth/VerifyCode', [
            'pendingEmail' => $user->email,
            'pendingUserId' => $user->id,
        ]);
    }

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
            throw ValidationException::withMessages([
                'code' => ['Verification session has expired. Please register again.'],
            ]);
        }

        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages([
                'code' => ['Your registration was rejected. Please contact support.'],
            ]);
        }

        if ($user->status === UserStatus::Active) {
            return VerificationResponse::alreadyActive($this->redirectPathFor($user));
        }

        $this->otpService->verify($user, $data->code);
        $this->approvalService->approve($user, null, 'otp');

        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->forget('pending_verification_user_id');

        return VerificationResponse::verified($this->redirectPathFor($user));
    }

    public function loginWeb(LoginWebRequest $request): RedirectResponse
    {
        $data = $request->toData();

        // AuthService decides whether the login string is an email or username.
        $user = $this->authService->findUserForLogin($data->login);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages([
                'login' => ['Your account was rejected. Please contact support.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'login' => ['Your account is pending verification. Please complete the 6-digit verification first.'],
            ]);
        }

        Auth::login($user, $data->remember);
        $request->session()->regenerate();

        return redirect($this->redirectPathFor($user))->with('success', 'Logged in successfully.');
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
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('instructor')) {
            return '/dashboard';
        }

        return '/';
    }
}
