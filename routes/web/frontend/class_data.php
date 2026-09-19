<?php

use App\Modules\Website\Controllers\ClassJoinController;
use App\Modules\Website\Controllers\StudentRegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/student-register', [StudentRegisterController::class, 'create'])
    ->name('frontend.student-register.create');

Route::post('/student-register', [StudentRegisterController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('frontend.student-register.store');

// Public QR join link. Classes are looked up by join_token - a random secret - and never by slug
// (course title + creation second), which anyone could guess. See StudyClass::uniqueJoinToken().
Route::get('/join-class/{studyClass:join_token}', [ClassJoinController::class, 'create'])
    ->name('frontend.class-join.create');

Route::post('/join-class/{studyClass:join_token}', [ClassJoinController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('frontend.class-join.store');

Route::get('/join-class/{studyClass:join_token}/approval-status', [ClassJoinController::class, 'approvalStatus'])
    ->middleware('throttle:60,1')
    ->name('frontend.class-join.approval-status');
