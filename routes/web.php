<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Route Loader
|--------------------------------------------------------------------------
*/

Route::group([], function (): void {
    includeRouteFiles(__DIR__ . '/web/backend');
    includeRouteFiles(__DIR__ . '/web/frontend');
});