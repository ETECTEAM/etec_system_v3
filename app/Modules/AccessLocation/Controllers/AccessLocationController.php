<?php

namespace App\Modules\AccessLocation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccessLocation;
use App\Models\GradingSetting;
use App\Modules\AccessLocation\Requests\StoreAccessLocationRequest;
use App\Modules\AccessLocation\Requests\UpdateLocationSettingsRequest;
use App\Modules\AccessLocation\Support\LockableRoutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AccessLocationController extends Controller
{
    public function index(): Response
    {
        $location = AccessLocation::query()
            ->with('routes:id,access_location_id,route_key')
            ->orderBy('id')
            ->first();

        return Inertia::render('backend/access-locations/Index', [
            'location' => $location ? $this->present($location) : null,
            'lockableRoutes' => LockableRoutes::options(),
            'featureEnabled' => (bool) setting(config('access-location.settings_key'), false),
            'sessionTtlSeconds' => (int) config('access-location.session_ttl'),
        ]);
    }

    /**
     * Create or update the single approved location and the routes it locks -
     * all from the one inline form on the index page.
     */
    public function save(StoreAccessLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $attributes = [
            'name' => $data['name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_meters' => $data['radius_meters'],
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ];

        $location = AccessLocation::query()->orderBy('id')->first();

        if ($location) {
            $location->update($attributes);
        } else {
            $attributes['created_by'] = $request->user()->id;
            $location = AccessLocation::create($attributes);
        }

        $this->syncRoutes($location, $data['route_keys'] ?? []);

        return back()->with('success', 'Location saved.');
    }

    public function destroy(AccessLocation $accessLocation): RedirectResponse
    {
        $accessLocation->delete();
        Cache::forget(AccessLocation::CACHE_KEY);

        return redirect()->route('access-locations.index')
            ->with('success', 'Location removed.');
    }

    /**
     * The master on/off button. Stored as a grading_settings row so it reads back
     * through the setting() helper the middleware already uses.
     */
    public function updateSettings(UpdateLocationSettingsRequest $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');

        GradingSetting::query()->updateOrCreate(
            ['key' => config('access-location.settings_key')],
            [
                'value' => $enabled ? 'true' : 'false',
                'type' => 'boolean',
                'label' => 'Location Lock Enabled',
                'group' => 'access_location',
                'updated_by' => $request->user()->id,
            ],
        );

        return back()->with('success', 'Location lock '.($enabled ? 'enabled' : 'disabled').'.');
    }

    /**
     * @param  list<string>  $keys
     */
    private function syncRoutes(AccessLocation $location, array $keys): void
    {
        // Rewritten wholesale rather than diffed - the list is short and this keeps
        // the pivot exactly in step with the form.
        $location->routes()->delete();

        $rows = collect($keys)
            ->unique()
            ->map(fn (string $key) => ['route_key' => $key])
            ->all();

        if ($rows !== []) {
            $location->routes()->createMany($rows);
        }

        // Bulk delete above does not fire model events, so bust the cache explicitly.
        Cache::forget(AccessLocation::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AccessLocation $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'radius_meters' => (int) $location->radius_meters,
            'is_active' => (bool) $location->is_active,
            'description' => $location->description,
            'route_keys' => $location->routes->pluck('route_key')->values()->all(),
        ];
    }
}
