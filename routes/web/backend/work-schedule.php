<?php

use App\Modules\WorkSchedule\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:work_schedule.view'])
    ->prefix('/dashboard/work-schedules')
    ->name('work-schedules.')
    ->group(function () {
        Route::get('/', [WorkScheduleController::class, 'index'])->name('index');
        Route::get('/create', [WorkScheduleController::class, 'create'])->name('create');
        Route::post('/', [WorkScheduleController::class, 'store'])->name('store');
        Route::get('/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->name('edit');
        Route::put('/{workSchedule}', [WorkScheduleController::class, 'update'])->name('update');
        Route::delete('/{workSchedule}', [WorkScheduleController::class, 'destroy'])->name('destroy');
    });
