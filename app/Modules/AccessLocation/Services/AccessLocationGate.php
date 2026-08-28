<?php

namespace App\Modules\AccessLocation\Services;

use App\Models\AccessLocation;
use App\Modules\AccessLocation\Support\LockableRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AccessLocationGate
{
    /**
     * Active locations with their locked-route keys, cached for the middleware.
     *
     * @return Collection<int, AccessLocation>
     */
    public function activeLocations(): Collection
    {
        return Cache::remember(
            AccessLocation::CACHE_KEY,
            (int) config('access-location.cache_ttl', 60),
            fn () => AccessLocation::query()
                ->with('routes:id,access_location_id,route_key')
                ->where('is_active', true)
                ->get(),
        );
    }

    /**
     * True when the feature is switched on both in config (hard kill switch) and
     * by the admin toggle (grading_settings row read via the setting() helper).
     */
    public function featureEnabled(): bool
    {
        if (! (bool) config('access-location.enabled', true)) {
            return false;
        }

        return (bool) setting(config('access-location.settings_key'), false);
    }

    /**
     * Active locations whose locked routes cover this request's path. Empty means
     * the path is not location-locked and the request should pass untouched.
     *
     * @return Collection<int, AccessLocation>
     */
    public function locationsCovering(Request $request): Collection
    {
        return $this->activeLocations()->filter(
            fn (AccessLocation $location) => $this->pathMatchesLocation($request, $location),
        )->values();
    }

    private function pathMatchesLocation(Request $request, AccessLocation $location): bool
    {
        foreach (LockableRoutes::patternsFor($location->routes->pluck('route_key')) as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nearest active location the coordinates fall inside, or null. A GPS fix whose
     * accuracy radius is worse than the location radius (or the configured slack) is
     * treated as unusable and skipped.
     *
     * @return array{location: AccessLocation, distance: float}|null
     */
    public function matchCoordinates(float $latitude, float $longitude, float $accuracy): ?array
    {
        $slack = (float) config('access-location.min_accuracy_slack', 50);
        $best = null;

        foreach ($this->activeLocations() as $location) {
            $radius = (float) $location->radius_meters;

            if ($accuracy > max($radius, $slack)) {
                continue;
            }

            $distance = $this->distanceMeters(
                (float) $location->latitude,
                (float) $location->longitude,
                $latitude,
                $longitude,
            );

            if ($distance <= $radius && ($best === null || $distance < $best['distance'])) {
                $best = ['location' => $location, 'distance' => $distance];
            }
        }

        return $best;
    }

    /**
     * Haversine great-circle distance in metres - same formula as
     * AttendanceQrService so location checks agree across the app.
     */
    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
