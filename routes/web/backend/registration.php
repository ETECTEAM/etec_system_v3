<?php

use App\Modules\Registration\Controllers\AdminClassController;
use App\Modules\Registration\Controllers\AdminRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('dashboard/admin')->name('admin.')->group(function () {
    // Classes setup
    Route::resource('classes', AdminClassController::class)->only(['index', 'create', 'store']);

    // Admin Registrations list and add
    Route::resource('registrations', AdminRegistrationController::class)->only(['index', 'create', 'store']);
});
