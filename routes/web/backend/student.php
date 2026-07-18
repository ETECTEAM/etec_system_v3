<?php

use App\Modules\EnRoll\Controllers\EnRollController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard/students')->group(function () {
    Route::get('/',             [EnRollController::class, 'index'])->name('students.index');
    Route::get('/create',       [EnRollController::class, 'create'])->name('students.create');
    Route::post('/',            [EnRollController::class, 'store'])->name('students.store');
    Route::get('/{class}',      [EnRollController::class, 'show'])->name('students.show');
    Route::get('/{class}/edit', [EnRollController::class, 'edit'])->name('students.edit');
    Route::put('/{class}',      [EnRollController::class, 'update'])->name('students.update');
    Route::delete('/{class}',   [EnRollController::class, 'destroy'])->name('students.destroy');
});
