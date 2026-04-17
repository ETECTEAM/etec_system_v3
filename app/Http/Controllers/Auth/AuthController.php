<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\AuthService;
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
    public function __construct(private readonly AuthService $authService) {}

    public function registerWeb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        [$user] = DB::transaction(function () use ($validated): array {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => false,
            ]);

            $role = $this->authService->ensureDefaultRole();

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $code = (string) random_int(100000, 999999);

            VerificationCode::create([
                'user_id' => $user->id,
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]);

            Notification::create([
                'title' => 'New Instructor Registered',
                'message' => $user->name.' registered. Code: '.$code,
                'is_read' => false,
            ]);

            return [$user];
        });

        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->put('pending_verification_user_id', $user->id);

        return redirect('/verify-code')->with('success', 'Registration received. Enter your verification code to activate your account.');
    }

    public function showVerifyCode(Request $request): Response|RedirectResponse
    {
        $pendingUserId = $request->session()->get('pending_verification_user_id');
        $user = $pendingUserId ? User::query()->find($pendingUserId) : null;

        if (! $user && $request->user() && ! $request->user()->is_active) {
            $user = $request->user();
            $request->session()->put('pending_verification_user_id', $user->id);
        }

        if (! $user) {
            return redirect('/register')->with('error', 'Please register first to request a verification code.');
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

        if (! $user && $authenticatedUser && ! $authenticatedUser->is_active) {
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

        $verificationCode = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('code', $validated['code'])
            ->where('is_verified', false)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if (! $verificationCode) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        $verificationCode->forceFill([
            'is_verified' => true,
        ])->save();

        $user->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

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

        if (! $user->is_active) {
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
