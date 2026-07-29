<?php
use App\Modules\Class\Controllers\ClassTypeController;
use App\Modules\Class\Controllers\ClassListController;
use Illuminate\Support\Facades\Route;

// Update the prefix to match your other dashboard routes
Route::middleware(['auth', 'active'])->prefix('dashboard')->group(function () {

    Route::controller(ClassTypeController::class)->prefix('class-types')->group(function () {
        Route::get('/', 'index')->name('class-types.index');
        Route::get('/create', 'create')->name('class-types.create');
        Route::post('/', 'store')->name('class-types.store');
        
        Route::get('/{id}', 'show')->name('class-types.show');
        Route::get('/{id}/edit', 'edit')->name('class-types.edit'); 
        Route::put('/{id}', 'update')->name('class-types.update');
        Route::delete('/{id}', 'destroy')->name('class-types.destroy');
    });


    Route::controller(ClassListController::class)->prefix('class-list')->group(function () {
        Route::get('/', 'index')->name('class-list.index');
        Route::get('/create', 'create')->name('class-list.create');
        Route::post('/', 'store')->name('class-list.store');

        Route::get('/{classList}', 'show')->name('class-list.show');
        Route::get('/{classList}/edit', 'edit')->name('class-list.edit');
        Route::put('/{classList}', 'update')->name('class-list.update');
        Route::delete('/{classList}', 'destroy')->name('class-list.destroy');
    });
});


