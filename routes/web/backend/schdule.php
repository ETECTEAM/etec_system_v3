<?php

use App\Modules\Schedules\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('/dashboard/schdule')
    ->name('schdule.')
    ->group(function () {

        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::get('/create', [ScheduleController::class, 'create'])->name('create');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');

        Route::get('/{schdule}/edit', [ScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schdule}', [ScheduleController::class, 'update'])->name('update');
        Route::delete('/{schdule}', [ScheduleController::class, 'destroy'])->name('destroy');
    });
