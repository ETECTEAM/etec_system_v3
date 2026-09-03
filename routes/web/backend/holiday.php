<?php

use App\Modules\Holiday\Controllers\HolidayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/holidays')
    ->name('holidays.')
    ->group(function (): void {
        // Route to show the holiday calendar.
        Route::get('/', [HolidayController::class, 'index'])->name('index');

        // Route to create a holiday date range.
        Route::post('/', [HolidayController::class, 'store'])->name('store');

        // Route to update a holiday date range.
        Route::put('/{groupId}', [HolidayController::class, 'update'])->name('update');

        // Route to delete a holiday date range.
        Route::delete('/{groupId}', [HolidayController::class, 'destroy'])->name('destroy');
    });
