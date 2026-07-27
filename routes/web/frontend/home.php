<?php


/*
|--------------------------------------------------------------------------
| Frontend Home Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based home page functionality for the frontend
| interface, including displaying the main home view and related operations.
|
*/

// Import necessary classes for route definitions and controller handling.
use Inertia\Inertia;
use App\Models\Page;
use App\Modules\Website\Services\WebsiteContentService;

// Import Route facade for defining web routes.
use Illuminate\Support\Facades\Route;

// Display the public home view.
Route::get('/', function (WebsiteContentService $website) {
    $homePage = Page::query()
        ->with('hero')
        ->where('slug', 'home')
        ->first();

    return Inertia::render('frontend/home/Home', [
        'courses' => $website->publicCourses(6),
        'pageData' => $homePage ? $website->presentPage($homePage) : null,
    ]);
});
