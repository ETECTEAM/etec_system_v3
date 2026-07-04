<?php

use App\Modules\ShiftTemplate\Controllers\ShiftTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:shift_template.view'])
    ->prefix('/dashboard/shift-templates')
    ->name('shift-templates.')
    ->group(function () {
        Route::get('/', [ShiftTemplateController::class, 'index'])->name('index');
        Route::get('/create', [ShiftTemplateController::class, 'create'])->name('create');
        Route::post('/', [ShiftTemplateController::class, 'store'])->name('store');
        Route::get('/{shiftTemplate}/edit', [ShiftTemplateController::class, 'edit'])->name('edit');
        Route::put('/{shiftTemplate}', [ShiftTemplateController::class, 'update'])->name('update');
        Route::delete('/{shiftTemplate}', [ShiftTemplateController::class, 'destroy'])->name('destroy');
    });
