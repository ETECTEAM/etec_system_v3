<?php

/*
|--------------------------------------------------------------------------
| Access Location (Location Lock) Routes
|--------------------------------------------------------------------------
|
| /dashboard/location/*      - the "share your location" interstitial and its
|                              verify endpoint. Any authenticated dashboard user
|                              can reach these; they are where EnforceLocationAccess
|                              sends a locked user.
| /dashboard/access-locations - one super-admin screen: the master on/off switch,
|                              the single approved location, and the routes it locks,
|                              all edited in place.
|
| Both prefixes are exempted inside EnforceLocationAccess so the feature can never
| lock the screens that control it.
|
*/

use App\Modules\AccessLocation\Controllers\AccessLocationController;
use App\Modules\AccessLocation\Controllers\LocationGateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->prefix('dashboard/location')
    ->name('location.')
    ->group(function (): void {
        Route::get('/gate', [LocationGateController::class, 'show'])->name('gate');
        Route::middleware('throttle:30,1')
            ->post('/verify', [LocationGateController::class, 'verify'])
            ->name('verify');
    });

Route::middleware(['auth', 'active', 'role:super_admin'])
    ->prefix('dashboard/access-locations')
    ->name('access-locations.')
    ->group(function (): void {
        Route::get('/', [AccessLocationController::class, 'index'])->name('index');
        Route::put('/settings', [AccessLocationController::class, 'updateSettings'])->name('settings');
        // Upsert the single location + its locked-route list from the inline form.
        Route::match(['post', 'put'], '/', [AccessLocationController::class, 'save'])->name('save');
        // Digits-only so a stale link like /access-locations/create 404s cleanly
        // instead of resolving here as a wrong-method match.
        Route::delete('/{accessLocation}', [AccessLocationController::class, 'destroy'])
            ->whereNumber('accessLocation')
            ->name('destroy');
    });
