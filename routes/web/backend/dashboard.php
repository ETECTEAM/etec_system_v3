<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'permission:dashboard.view'])->group(function () {
    Route::get('/dashboard', function () {
        return inertia('backend/Home');
    })->name('dashboard');
});