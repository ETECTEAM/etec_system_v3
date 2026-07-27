<?php

use App\Modules\Times\Controllers\TimeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->prefix('/dashboard/times')
    ->name('times.')
    ->group(function () {

        Route::get('/', [TimeController::class, 'index'])->name('index');
        Route::get('/create', [TimeController::class, 'create'])->name('create');
        Route::post('/', [TimeController::class, 'store'])->name('store');

        Route::get('/{time}/edit', [TimeController::class, 'edit'])->name('edit');
        Route::put('/{time}', [TimeController::class, 'update'])->name('update');
        Route::delete('/{time}', [TimeController::class, 'destroy'])->name('destroy');
    });