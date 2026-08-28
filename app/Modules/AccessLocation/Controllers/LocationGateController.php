<?php

namespace App\Modules\AccessLocation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LocationAccessLog;
use App\Modules\AccessLocation\Requests\VerifyLocationRequest;
use App\Modules\AccessLocation\Services\AccessLocationGate;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocationGateController extends Controller
{
    public function __construct(private readonly AccessLocationGate $gate) {}

    /**
     * The interstitial a location-locked user is bounced to. The page asks the
     * browser for GPS, posts it to verify(), then returns the user to where they
     * were headed.
     */
    public function show(): Response
    {
        return Inertia::render('backend/location/Gate', [
            'intended' => session('url.intended', route('dashboard')),
            'ttlSeconds' => (int) config('access-location.session_ttl'),
        ]);
    }

    public function verify(VerifyLocationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $match = $this->gate->matchCoordinates(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $data['accuracy'],
        );

        LocationAccessLog::create([
            'user_id' => $request->user()->id,
            'path' => (string) session('url.intended'),
            'outcome' => $match ? 'verified' : 'rejected',
            'access_location_id' => $match['location']->id ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'],
            'distance_meters' => $match ? (int) round($match['distance']) : null,
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        if (! $match) {
            return response()->json([
                'matched' => false,
                'message' => 'You are not inside an approved location. Move closer and try again.',
            ], 422);
        }

        // Stamp the session; the middleware trusts this until it goes stale.
        $request->session()->put('location_gate', [
            'location_id' => $match['location']->id,
            'verified_at' => now()->timestamp,
        ]);

        return response()->json([
            'matched' => true,
            'location' => $match['location']->name,
            'redirect' => session('url.intended', route('dashboard')),
        ]);
    }
}
