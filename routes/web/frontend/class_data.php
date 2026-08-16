<?php

use App\Modules\Website\Controllers\StudentRegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/student-register', [StudentRegisterController::class, 'create'])
    ->name('frontend.student-register.create');

Route::post('/student-register', [StudentRegisterController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('frontend.student-register.store');
