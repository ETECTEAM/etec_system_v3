<?php

use App\Modules\Instructor\Controllers\InstructorClassController;
use App\Modules\Instructor\Controllers\InstructorProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:instructor'])->prefix('/dashboard/instructor')->group(function () {
    Route::get('/', [InstructorProfileController::class, 'show']);
    Route::get('/profile', [InstructorProfileController::class, 'edit']);
    Route::put('/profile', [InstructorProfileController::class, 'update'])->middleware('throttle:10,1');

    Route::get('/classes/create', [InstructorClassController::class, 'create'])->name('instructor.classes.create');
    Route::post('/classes', [InstructorClassController::class, 'store'])->name('instructor.classes.store');
});
