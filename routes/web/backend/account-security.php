<?php

/*
|--------------------------------------------------------------------------
| Account Security Routes
|--------------------------------------------------------------------------
|
| Self-service recovery-email management, available to every logged-in
| role - unlike login-security.php, which is admin-only global lockout
| config. The verify link is guest-safe: the signature itself is the proof
| of mailbox access, so requiring an active session too would only break
| clicking the link from a different device than the one that submitted
| the change.
|
*/
use App\Modules\Account\Controllers\AccountSecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->prefix('/dashboard/account-security')
    ->name('account-security.')
    ->group(function () {
        // Route to display the current recovery-email status.
        Route::get('/', [AccountSecurityController::class, 'edit'])->name('edit');

        // Route to submit a new/changed recovery email; throttled since it triggers outbound mail.
        Route::middleware('throttle:5,1')->post('/recovery-email', [AccountSecurityController::class, 'updateRecoveryEmail'])->name('recovery-email.update');

        // Route to resend the verification link for an unverified recovery email; throttled for the same reason.
        Route::middleware('throttle:5,1')->post('/recovery-email/resend', [AccountSecurityController::class, 'resendVerification'])->name('recovery-email.resend');
    });

// Route to verify a recovery email from its signed link; guest-safe, see comment above.
Route::middleware('signed')->get('/account-security/recovery-email/verify/{user}', [AccountSecurityController::class, 'verifyRecoveryEmail'])->name('account-security.recovery-email.verify');
