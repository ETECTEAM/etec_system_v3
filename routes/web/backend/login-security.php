<?php

/*
|--------------------------------------------------------------------------
| Login Security Routes
|--------------------------------------------------------------------------
|
| Two separately-permissioned groups: configuring the escalating
| account-lockout tiers (super_admin only), and viewing/unblocking
| currently-blocked accounts (super_admin and admin). See
| database/seeders/Permission/AssignPermissionSeeder.php for the grants.
|
*/
use App\Modules\Auth\Controllers\LoginLockoutController;
use App\Modules\Auth\Controllers\LoginSecuritySettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:manage-login-security'])
    ->prefix('/dashboard/login-security')
    ->name('login-security.')
    ->group(function () {
        // Route to display the current lockout tiers and reset window.
        Route::get('/', [LoginSecuritySettingsController::class, 'edit'])->name('edit');

        // Route to replace the lockout tiers and update the reset window.
        Route::put('/', [LoginSecuritySettingsController::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'permission:unblock-login-accounts'])
    ->prefix('/dashboard/login-security/blocked-accounts')
    ->name('login-security.blocked.')
    ->group(function () {
        // Route to list accounts currently blocked by LoginLockoutService.
        Route::get('/', [LoginLockoutController::class, 'index'])->name('index');

        // Route to lift an account's active block early.
        Route::post('/unblock', [LoginLockoutController::class, 'unblock'])->name('unblock');
    });
