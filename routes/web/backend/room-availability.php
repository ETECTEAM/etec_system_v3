<?php

use App\Modules\Room\Controllers\RoomAvailabilityController;
use Illuminate\Support\Facades\Route;

// Which rooms are free or taken at each schedule slot: admin and super admin only.
Route::middleware(['auth', 'active', 'role:super_admin|admin', 'throttle:60,1'])
    ->prefix('/dashboard/room-availability')
    ->name('room-availability.')
    ->group(function () {
        // Route to show the free/taken room grid for a class type's schedule.
        Route::get('/', [RoomAvailabilityController::class, 'index'])->name('index');
    });
