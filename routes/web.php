<?php

use App\Http\Middleware\RedirectAdminFromFrontend;
use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
|
| Public-facing pages and resident portal routes.
| Keep these after backend routes so the frontend catch-all does not
| override auth endpoints like /login and /register.
|
*/

Route::middleware(RedirectAdminFromFrontend::class)->group(function (): void {
    includeRouteFiles(__DIR__.'/web/frontend');
});
