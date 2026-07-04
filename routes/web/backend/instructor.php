<?php

use App\Modules\Instructor\Controllers\InstructorProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('/dashboard/instructor')->group(function () {
    Route::get('/', [InstructorProfileController::class, 'show']);
    Route::get('/profile', [InstructorProfileController::class, 'edit']);
    Route::put('/profile', [InstructorProfileController::class, 'update'])->middleware('throttle:10,1');
});
