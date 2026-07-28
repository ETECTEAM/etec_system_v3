<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;

/*
|--------------------------------------------------------------------------
| Web Route Loader
|--------------------------------------------------------------------------
*/

Route::group([], function (): void {
    Route::post('/locale', [LocaleController::class, 'set'])->name('locale.set');

    includeRouteFiles(__DIR__ . '/web/backend');
    includeRouteFiles(__DIR__ . '/web/frontend');
});
