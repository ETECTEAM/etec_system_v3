<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => Auth::user()?->only('id', 'name', 'email', 'role'),
                'roles' => fn () => Auth::check() ? Auth::user()->getRoleNames()->values()->all() : [],
                'permissions' => fn () => Auth::check() ? Auth::user()->getAllPermissions()->pluck('name')->values()->all() : [],
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                // Seconds until a throttle block lifts - lets the triggering form
                // disable its submit button and count down instead of just erroring.
                'retryAfter' => $request->session()->get('retryAfter'),
                // True when a retryAfter block is an account-wide hard block
                // (past every configured lockout tier), not a regular timed
                // one - lets the triggering form show "contact an admin"
                // instead of a plain countdown.
                'isHardBlock' => $request->session()->get('isHardBlock'),
            ],
        ];
    }
}
