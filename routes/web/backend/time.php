<?php

use App\Modules\Times\Controllers\TimeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/times')
    ->name('times.')
    ->group(function () {

        Route::get('/', [TimeController::class, 'index'])->middleware('permission:view-times')->name('index');

        Route::middleware('permission:manage-times')->group(function () {
            Route::get('/create', [TimeController::class, 'create'])->name('create');
            Route::post('/', [TimeController::class, 'store'])->name('store');
            Route::get('/{time}/edit', [TimeController::class, 'edit'])->name('edit');
            Route::put('/{time}', [TimeController::class, 'update'])->name('update');
            Route::delete('/{time}', [TimeController::class, 'destroy'])->name('destroy');
        });
    });