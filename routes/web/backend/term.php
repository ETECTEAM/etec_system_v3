<?php

use App\Modules\Terms\Controllers\TermController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/terms')
    ->name('terms.')
    ->group(function () {

        Route::get('/', [TermController::class, 'index'])->name('index');
        Route::get('/create', [TermController::class, 'create'])->name('create');
        Route::post('/', [TermController::class, 'store'])->name('store');

        Route::get('/{term}/edit', [TermController::class, 'edit'])->name('edit');
        Route::put('/{term}', [TermController::class, 'update'])->name('update');
        Route::delete('/{term}', [TermController::class, 'destroy'])->name('destroy');
    });