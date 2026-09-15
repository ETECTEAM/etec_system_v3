<?php

/*
|--------------------------------------------------------------------------
| OTP Verification Settings Routes
|--------------------------------------------------------------------------
|
| Lets a super_admin toggle whether instructor self-registration requires
| OTP verification before activation. See
| database/seeders/Permission/PermissionSeeder.php for the permission grant
| (super_admin receives every permission automatically).
|
*/
use App\Modules\Auth\Controllers\OtpVerificationSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:manage-otp-settings'])
    ->prefix('/dashboard/otp-settings')
    ->name('otp-settings.')
    ->group(function () {
        // Route to display the current OTP verification toggle.
        Route::get('/', [OtpVerificationSettingController::class, 'edit'])->name('edit');

        // Route to update the OTP verification toggle.
        Route::put('/', [OtpVerificationSettingController::class, 'update'])->name('update');
    });
