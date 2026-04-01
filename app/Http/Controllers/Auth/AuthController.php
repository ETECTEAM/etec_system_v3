<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($validated);

        // Ensure the default API role exists for the configured Sanctum guard.
        $role = $this->authService->ensureDefaultRole();

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(
            $this->authService->buildAuthPayload($user, $token, 'Registered successfully.'),
            201
        );
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['nullable', 'string', 'required_without:email'],
            'email' => ['nullable', 'string', 'required_without:login'],
            'password' => ['required', 'string'],
        ]);

        // Support either the new "login" field or the legacy "email" field.
        $login = trim((string) ($credentials['login'] ?? $credentials['email'] ?? ''));

        // Resolve the user by email when possible, otherwise fall back to name login.
        $user = $this->authService->findUserForLogin($login);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(
            $this->authService->buildAuthPayload($user, $token, 'Logged in successfully.')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($this->authService->buildAuthPayload($user));
    }
}
