<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Events\PendingUserRegistered;
use App\Http\Controllers\Controller;
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

    public function registerWeb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        [$user, $otp, $plainCode] = DB::transaction(function () use ($validated): array {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
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

    public function verifyCodeApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $authenticatedUser = $request->user();
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        if (! $user && $authenticatedUser && $authenticatedUser->status !== UserStatus::Active) {
            $user = $authenticatedUser;
            $pendingUserId = $authenticatedUser->id;
        }

        if (! $user && isset($validated['user_id'])) {
            $user = User::query()->find((int) $validated['user_id']);
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
            return response()->json([
                'message' => 'Account is already active.',
                'redirect' => $this->redirectPathFor($user),
            ]);
        }

        $this->otpService->verify($user, $validated['code']);
        $this->approvalService->approve($user, null, 'otp');

        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->forget('pending_verification_user_id');

        return response()->json([
            'message' => 'Account verified successfully.',
            'redirect' => $this->redirectPathFor($user),
        ]);
    }

    public function loginWeb(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['nullable', 'string', 'required_without:email'],
            'email' => ['nullable', 'string', 'required_without:login'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) ($credentials['login'] ?? $credentials['email'] ?? ''));
        $user = $this->authService->findUserForLogin($login);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
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

        Auth::login($user, $request->boolean('remember'));
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
