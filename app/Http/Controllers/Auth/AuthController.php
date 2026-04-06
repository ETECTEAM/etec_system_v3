<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function registerWeb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $role = $this->authService->ensureDefaultRole();

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('success', 'Registered successfully.');
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

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect('/')->with('success', 'Logged in successfully.');
    }

    public function logoutWeb(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
