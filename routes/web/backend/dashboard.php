<?php


/*
|--------------------------------------------------------------------------
| Backend Dashboard Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based dashboard functionality for the backend
| interface, including displaying the main dashboard view and related operations.
| Authenticated users can access these routes.
|
*/

// Import necessary classes for route definitions and controller handling.
use Illuminate\Support\Facades\Route;

// Import Inertia for rendering frontend views in the dashboard.
use Inertia\Inertia;

// Display the main dashboard view for authenticated users.
Route::middleware('auth')->get('/dashboard', function () {
    // Render the 'backend/Home' view using Inertia for the dashboard page.
    return Inertia::render('backend/Home');
})->name('dashboard');
