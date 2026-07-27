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
use App\Modules\Website\Services\WebsiteContentService;

// Import Auth facade for checking user authentication status.
use Illuminate\Support\Facades\Auth;

// Import Route facade for defining web routes.
use Illuminate\Support\Facades\Route;

// Display the main home view for guest users or redirect authenticated users to the dashboard.
Route::get('/', function (WebsiteContentService $website) {
    if (Auth::check() && Auth::user()->can('dashboard.view')) {
        return redirect('/dashboard');
    }

    return Inertia::render('frontend/home/Home', [
        'courses' => $website->publicCourses(6),
    ]);
});
