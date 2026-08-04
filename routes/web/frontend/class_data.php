<?php

use App\Modules\Website\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/classes', [PublicPageController::class, 'classes'])
    ->name('frontend.classes.index');

Route::get('/classes/load-more', [PublicPageController::class, 'classesLoadMore'])
    ->name('pages.classes.load-more');

Route::post('/classes/{studyClass}/join', [PublicPageController::class, 'joinClass'])
    ->name('frontend.classes.join');
