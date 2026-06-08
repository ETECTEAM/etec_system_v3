<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Events\PendingUserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginWebRequest;
use App\Http\Requests\Auth\RegisterWebRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Http\Responses\Auth\VerificationResponse;
use App\Models\User;
use App\Services\Auth\AuthAuditService;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Services\Users\UserApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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

        [$user, $otp, $plainCode] = DB::transaction(function () use ($data): array {
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

            [$otp, $plainCode] = $this->otpService->createForUser($user);

            return [$user, $otp, $plainCode];
        });

        $this->auditService->log($user, 'user.registered', $request->ip());

        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->put('pending_verification_user_id', $user->id);

        PendingUserRegistered::dispatch($user, $otp, $plainCode);

        return redirect('/code-verify')->with('success', 'Registration received. Enter your verification code to activate your account.');
    }

    public function showVerifyCode(Request $request): Response|RedirectResponse
    {
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

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
