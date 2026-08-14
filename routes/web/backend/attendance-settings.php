<?php

use App\Modules\Attendance\Controllers\AttendanceSettingsController;
use Illuminate\Support\Facades\Route;

// Auto-record attendance settings: superadmin only.
Route::middleware(['auth', 'active', 'role:super_admin'])
    ->prefix('/dashboard/attendance-settings')
    ->name('attendance-settings.')
    ->group(function () {
        // Route to display the current auto-record settings.
        Route::get('/', [AttendanceSettingsController::class, 'edit'])->name('edit');

        // Route to update the auto-record settings.
        Route::put('/', [AttendanceSettingsController::class, 'update'])->name('update');
    });
