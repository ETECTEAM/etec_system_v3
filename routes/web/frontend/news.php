<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Modules\Website\Services\WebsiteContentService;

/*
|--------------------------------------------------------------------------
| News List
|--------------------------------------------------------------------------
*/

Route::get('/news', function (
    Request $request,
    WebsiteContentService $website
) {
    $validated = $request->validate([
        'search' => ['nullable', 'string', 'max:120'],
        'featured' => ['nullable'],
        'page' => ['nullable', 'integer', 'min:1'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
    ]);

    $filters = [];

    if ($request->filled('search')) {
        $filters['search'] = $validated['search'];
    }

    if ($request->has('featured')) {
        $filters['featured'] = $request->input('featured');
    }

    return Inertia::render('frontend/news/News', [
        'news' => $website->paginatedPublicNews(
            (int) ($validated['per_page'] ?? 12),
            $filters,
        ),

        'filters' => [
            'search' => $validated['search'] ?? '',
            'featured' => $request->input('featured'),
            'per_page' => (int) ($validated['per_page'] ?? 12),
        ],
    ]);
})->name('news.index');

/*
|--------------------------------------------------------------------------
| News Detail
|--------------------------------------------------------------------------
*/

Route::get('/news/{slug}', function (
    string $slug,
    WebsiteContentService $website
) {
    $news = $website->publicNewsDetail($slug);

    abort_unless($news, 404);

    return Inertia::render('frontend/news/NewsDetails', [
        'news' => $news,
    ]);
})->name('news.show');