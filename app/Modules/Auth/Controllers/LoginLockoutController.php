<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\LoginLockoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginLockoutController extends Controller
{
    public function __construct(
        private readonly LoginLockoutService $lockoutService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('backend/login-security/BlockedAccounts', [
            'blocked' => $this->lockoutService->blocked()->map(fn ($lockout) => [
                'login' => $lockout->login,
                'offense_number' => $lockout->offense_number,
                'banned_until' => $lockout->banned_until,
                'is_hard_block' => $lockout->is_hard_block,
            ]),
        ]);
    }

    public function unblock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
        ]);

        $this->lockoutService->unblock($validated['login']);

        return redirect()->route('login-security.blocked.index')
            ->with('success', "Unblocked {$validated['login']}.");
    }
}
