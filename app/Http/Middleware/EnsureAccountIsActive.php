<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a logged-in-but-not-yet-approved user (status pending/inactive/rejected)
 * from reaching protected routes. Registration logs the user in immediately so
 * /code-verify can resolve them from the session, which otherwise leaves an
 * authenticated-but-unverified session free to reach anything 'auth' allows.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== UserStatus::Active) {
            $request->session()->put('pending_verification_user_id', $user->id);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please verify your account before continuing.'], 403);
            }

            return redirect('/code-verify')
                ->with('error', 'Please verify your account before continuing.');
        }

        return $next($request);
    }
}
