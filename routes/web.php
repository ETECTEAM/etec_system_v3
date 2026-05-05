<?php

use App\Http\Middleware\RedirectAdminFromFrontend;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
|
| Public-facing pages and resident portal routes.
|
*/

Route::middleware(RedirectAdminFromFrontend::class)->group(function (): void {
    includeRouteFiles(__DIR__.'/web/frontend');
});

/*
|--------------------------------------------------------------------------
| Backend Routes
|--------------------------------------------------------------------------
|
| Authentication and other backend-oriented web endpoints.
|
*/

Route::group([], function (): void {
    includeRouteFiles(__DIR__.'/web/backend');
});
