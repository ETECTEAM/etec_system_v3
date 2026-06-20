<?php

use App\Modules\Class\Controllers\ClassCategoryController;
use App\Modules\Class\Controllers\ClassTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {


    // ----------------------------------------------------
    // 1. Class Type
    // ----------------------------------------------------
    Route::controller(ClassTypeController::class)->prefix('class-types')->group(function () {
        Route::get('/', 'index')->name('class-types.index');
        Route::get('/create', 'create')->name('class-types.create');
        Route::post('/', 'store')->name('class-types.store');
        
        Route::get('/{id}', 'show')->name('class-types.show');
        Route::get('/{id}/edit', 'edit')->name('class-types.edit');
        Route::put('/{id}', 'update')->name('class-types.update');
        Route::delete('/{id}', 'destroy')->name('class-types.destroy');
    });
    
    // ----------------------------------------------------
    // 2. Class Category
    // ----------------------------------------------------
    Route::controller(ClassCategoryController::class)->prefix('class-categories')->group(function () {
        Route::get('/', 'index')->name('class-categories.index');
        Route::get('/create', 'create')->name('class-categories.create');
        Route::post('/', 'store')->name('class-categories.store');
        
        Route::get('/{id}', 'show')->name('class-categories.show');
        Route::get('/{id}/edit', 'edit')->name('class-categories.edit');
        Route::put('/{id}', 'update')->name('class-categories.update');
        Route::delete('/{id}', 'destroy')->name('class-categories.destroy');
    });

    
    
});