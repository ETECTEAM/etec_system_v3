<?php

use App\Modules\Website\Controllers\PublicPageController;
use App\Modules\Website\Controllers\StudentRegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/classes', [PublicPageController::class, 'classes'])
    ->name('frontend.classes.index');

Route::get('/classes/load-more', [PublicPageController::class, 'classesLoadMore'])
    ->name('pages.classes.load-more');

Route::post('/classes/{studyClass}/register', [PublicPageController::class, 'registerForClass'])
    ->middleware('throttle:5,10')
    ->name('frontend.classes.register');

Route::get('/public/enrollments/{enrollment}/status', [PublicPageController::class, 'enrollmentStatus'])
    ->name('frontend.enrollments.status');

Route::get('/student-register', [StudentRegisterController::class, 'create'])
    ->name('frontend.student-register.create');

Route::post('/student-register', [StudentRegisterController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('frontend.student-register.store');
