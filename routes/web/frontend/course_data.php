<?php

use App\Modules\Website\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/course/load-more', [PublicPageController::class, 'courses'])
    ->name('pages.course.load-more');

Route::get('/courses/load-more', [PublicPageController::class, 'courses'])
    ->name('pages.courses.load-more');

Route::get('/courses/{slug}', [PublicPageController::class, 'courseDetail'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('frontend.courses.show');
