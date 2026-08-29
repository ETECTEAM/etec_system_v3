<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Modules\Auth\Services\AuthAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a logged-in-but-not-yet-approved user (status pending/inactive/rejected)
 * from reaching protected routes. Registration logs the user in immediately so
 * /code-verify can resolve them from the session, which otherwise leaves an
 * authenticated-but-unverified session free to reach anything 'auth' allows.
 *
 * Also enforces the role-based token/session expiry mid-session: once a
 * user's access window expires while they are logged in, the next protected
 * request logs them out and sends them back to the login page, where signing
 * in again mints a fresh token.
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

        // The role-based token/session expires when its deadline passes (or
        // the stored deadline isn't strictly ahead of the last renewal): end
        // the session and send the user back to login, where signing in mints
        // a fresh token. Roles with no configured lifetime never expire.
        if ($user && $user->accessWindowInvalid()) {
            app(AuthAuditService::class)->log($user, 'login.access_expired', $request->ip());

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your access has expired. Please log in again to renew your access.'], 401);
            }

            return redirect('/login')
                ->with('error', 'Your access has expired. Please log in again to renew your access.');
        }

        return $next($request);
    }
}
