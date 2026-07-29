<?php

use App\Modules\Auth\Controllers\Telegram\TelegramWebhookController;
use App\Modules\Website\Controllers\PublicApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:telegram-webhook')->post('/telegram/webhook', TelegramWebhookController::class);

Route::prefix('public')->name('api.public.')->group(function (): void {
    Route::get('/school-settings', [PublicApiController::class, 'schoolSettings'])->name('school-settings');
    Route::get('/navigation', [PublicApiController::class, 'navigation'])->name('navigation');
    Route::get('/home', [PublicApiController::class, 'home'])->name('home');
    Route::get('/featured', [PublicApiController::class, 'featured'])->name('featured');
    Route::get('/pages/{slug}', [PublicApiController::class, 'page'])->name('pages.show');

    Route::get('/courses', [PublicApiController::class, 'courses'])->name('courses.index');
    Route::get('/courses/{slug}', [PublicApiController::class, 'course'])->name('courses.show');

    Route::get('/news', [PublicApiController::class, 'news'])->name('news.index');
    Route::get('/news/{slug}', [PublicApiController::class, 'newsDetail'])->name('news.show');

    Route::get('/events', [PublicApiController::class, 'emptyCollection'])->name('events.index');
    Route::get('/events/{slug}', [PublicApiController::class, 'emptyDetail'])->name('events.show');

    Route::get('/videos', [PublicApiController::class, 'videos'])->name('videos.index');
    Route::get('/videos/{video}', [PublicApiController::class, 'video'])->whereNumber('video')->name('videos.show');

    Route::post('/contact', [PublicApiController::class, 'contact'])->name('contact');
});
