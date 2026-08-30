<?php

use App\Modules\Instructor\Controllers\InstructorAvailabilityController;
use Illuminate\Support\Facades\Route;

// Admin/super_admin only: a weekly "busy time" grid across every instructor
// (occupying classes + manual blocks). Instructors self-manage their own
// availability from the instructor dashboard rather than here.
Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/instructor-availability')
    ->name('instructor-availability.')
    ->group(function (): void {
        // Route to view the weekly instructor availability/busy-time grid.
        Route::get('/', [InstructorAvailabilityController::class, 'index'])->name('index');
    });
