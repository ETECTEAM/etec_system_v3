<?php

namespace App\Http\Middleware;

use App\Modules\AccessLocation\Services\AccessLocationGate;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Location lock. When the feature is switched on, a request whose path is covered
 * by an active AccessLocation only passes if the session carries a fresh GPS check
 * that put the user inside one of the locations covering that path. Otherwise the
 * user is sent to the location screen (/dashboard/location/gate) to share their
 * position.
 *
 * Everything is a no-op unless the feature is on AND some active location actually
 * locks the requested path, so this stays cheap on the vast majority of requests.
 */
class EnforceLocationAccess
{
    public function __construct(private readonly AccessLocationGate $gate) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->gate->featureEnabled()) {
            return $next($request);
        }

        // The screens that manage the feature, the location interstitial itself, and
        // logout must never be locked - locking any of them would trap the user.
        if ($request->is(
            'dashboard/access-locations',
            'dashboard/access-locations/*',
            'dashboard/location/*',
            'logout',
        )) {
            return $next($request);
        }

        foreach ((array) config('access-location.bypass_roles', []) as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        $covering = $this->gate->locationsCovering($request);

        if ($covering->isEmpty()) {
            return $next($request);
        }

        $stamp = $request->session()->get('location_gate');
        $ttl = (int) config('access-location.session_ttl', 900);

        $fresh = is_array($stamp)
            && isset($stamp['verified_at'], $stamp['location_id'])
            && (now()->timestamp - (int) $stamp['verified_at']) <= $ttl;

        if (! $fresh) {
            return $this->sendToGate($request);
        }

        // The stamped location must be one that covers this specific path.
        if (! $covering->contains('id', (int) $stamp['location_id'])) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function sendToGate(Request $request): Response
    {
        $request->session()->put('url.intended', $request->fullUrl());

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('location.gate'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Location verification required.',
                'redirect' => route('location.gate'),
            ], 409);
        }

        return redirect()->route('location.gate');
    }

    private function deny(Request $request): Response
    {
        $message = 'This section is only available at an approved location.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $message], 403);
        }

        // For Inertia (GET or form) a redirect with a flash is the friendly path -
        // HandleInertiaRequests upgrades it to 303 for PUT/PATCH/DELETE.
        return redirect()->route('dashboard')->with('error', $message);
    }
}
