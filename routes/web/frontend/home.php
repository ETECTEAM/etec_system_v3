<?php


/*
|--------------------------------------------------------------------------
| Frontend Home Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based home page functionality for the frontend
| interface, including displaying the main home view and related operations.
| Guest-only access and request throttling are applied where appropriate.
|
*/

// Import necessary classes for route definitions and controller handling.
use Inertia\Inertia;

// Import Auth facade for checking user authentication status.
use Illuminate\Support\Facades\Auth;

// Import Route facade for defining web routes.
use Illuminate\Support\Facades\Route;

// Display the main home view for guest users or redirect authenticated users to the dashboard.
Route::get('/', function () {
    if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'))) {
        return redirect('/dashboard');
    }

    return Inertia::render('frontend/home/Home');
});

// Catch-all route to handle undefined frontend paths.
// Keep this route last so it does not override auth routes like /login or /register.
Route::get('/{any}', function () {
    return Inertia::render('frontend/home/Home');
})->where('any', '.*');
