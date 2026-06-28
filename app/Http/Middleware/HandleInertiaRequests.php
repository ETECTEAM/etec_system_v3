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
        ];
    }
}
