<?php

use App\Modules\Instructor\Controllers\InstructorScheduleBlockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:instructor'])
    ->prefix('/dashboard/instructor-schedule-blocks')
    ->name('instructor-schedule-blocks.')
    ->group(function () {
        // Route to view the authenticated instructor's schedule block page.
        Route::get('/', [InstructorScheduleBlockController::class, 'index'])->name('index');
        // Route to get the schedule grid data for the authenticated instructor.
        Route::get('/data', [InstructorScheduleBlockController::class, 'data'])->name('data');
        // Route to block a working time slot.
        Route::post('/', [InstructorScheduleBlockController::class, 'store'])->name('store');
        // Route to block all available slots in a calendar row at once.
        Route::post('/row', [InstructorScheduleBlockController::class, 'storeRow'])->name('store-row');
        // Route to remove all manual blocks in a calendar row at once.
        Route::delete('/row', [InstructorScheduleBlockController::class, 'destroyRow'])->name('destroy-row');
        // Route to remove a manual block.
        Route::delete('/block/{block}', [InstructorScheduleBlockController::class, 'destroy'])->name('destroy');
    });
