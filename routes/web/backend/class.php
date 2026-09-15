<?php
use App\Modules\Class\Controllers\ClassTypeController;
use App\Modules\Class\Controllers\ClassListController;
use Illuminate\Support\Facades\Route;

// Update the prefix to match your other dashboard routes
// Admin-only: instructors manage their own classes from the instructor dashboard, and the
// sidebar hides this section for them (resources/js/layouts/menu/classes.js).
Route::middleware(['auth', 'active', 'role:super_admin|admin'])->prefix('dashboard')->group(function () {

    // Per-route permission middleware (not grouped by permission) so literal
    // paths like /create keep registering before the dynamic /{id} route -
    // grouping by permission type earlier put /create after /{id}, which
    // matched "create" as the {id} parameter instead and 404'd.
    Route::controller(ClassTypeController::class)->prefix('class-types')->group(function () {
        Route::get('/', 'index')->middleware('permission:view-classes')->name('class-types.index');
        Route::get('/data', 'paginatedIndex')->middleware('permission:view-classes')->name('class-types.data');
        Route::get('/create', 'create')->middleware('permission:create-classes')->name('class-types.create');
        Route::post('/', 'store')->middleware('permission:create-classes')->name('class-types.store');

        Route::get('/{id}', 'show')->middleware('permission:view-classes')->name('class-types.show');
        Route::get('/{id}/edit', 'edit')->middleware('permission:edit-classes')->name('class-types.edit');
        Route::put('/{id}', 'update')->middleware('permission:edit-classes')->name('class-types.update');
        Route::delete('/{id}', 'destroy')->middleware('permission:delete-classes')->name('class-types.destroy');
    });


    Route::controller(ClassListController::class)->prefix('class-list')->group(function () {
        Route::get('/', 'index')->middleware('permission:view-classes')->name('class-list.index');
        Route::get('/create', 'create')->middleware('permission:create-classes')->name('class-list.create');
        Route::post('/', 'store')->middleware('permission:create-classes')->name('class-list.store');

        Route::get('/{classList}', 'show')->middleware('permission:view-classes')->name('class-list.show');
        Route::get('/{classList}/edit', 'edit')->middleware('permission:edit-classes')->name('class-list.edit');
        Route::put('/{classList}', 'update')->middleware('permission:edit-classes')->name('class-list.update');
        Route::delete('/{classList}', 'destroy')->middleware('permission:delete-classes')->name('class-list.destroy');
    });
});
