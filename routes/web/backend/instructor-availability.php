<?php

use App\Modules\Instructor\Controllers\InstructorAvailabilityController;
use Illuminate\Support\Facades\Route;

// Admin/super_admin only: a weekly "busy time" grid across every instructor
// (occupying classes + manual blocks + availability windows). Admins can also
// block/unblock a slot, open/close a non-working slot, and toggle an
// instructor's available-for-class switch straight from the grid. Instructors
// self-manage their own availability from the instructor dashboard instead.
Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/instructor-availability')
    ->name('instructor-availability.')
    ->group(function (): void {
        // View the weekly instructor availability/busy-time grid.
        Route::get('/', [InstructorAvailabilityController::class, 'index'])->name('index');
        // Grid data as JSON (re-fetched after every edit).
        Route::get('/data', [InstructorAvailabilityController::class, 'data'])->name('data');

        // Block / unblock a working slot for an instructor.
        Route::post('/block', [InstructorAvailabilityController::class, 'blockSlot'])->name('block');
        Route::delete('/block/{block}', [InstructorAvailabilityController::class, 'unblockSlot'])->name('unblock');

        // Open / close a slot outside the instructor's work schedule.
        Route::post('/open', [InstructorAvailabilityController::class, 'openSlot'])->name('open');
        Route::delete('/open/{availability}', [InstructorAvailabilityController::class, 'closeSlot'])->name('close');

        // Toggle an instructor's available-for-class master switch.
        Route::patch('/instructor/{instructor}', [InstructorAvailabilityController::class, 'toggleAvailableForClass'])->name('toggle-available');
    });
