<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminFromFrontend
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin')) && $request->path() === '/') {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
