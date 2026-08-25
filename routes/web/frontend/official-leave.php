<?php

use App\Modules\OfficialLeave\Controllers\LeaveRequestController;
use Illuminate\Support\Facades\Route;

// Public leave-request form behind the office's signed QR: no login required —
// the student scans and submits from their own phone. The signature carries the
// 15-minute expiry; single-use is enforced against the hashed session row when
// the form is submitted. GET renders the form, POST submits to the same signed URL.
Route::middleware('signed')->group(function (): void {
    Route::get('/leave-request/{token}', [LeaveRequestController::class, 'showPublicForm'])->name('leave.form');
    Route::post('/leave-request/{token}', [LeaveRequestController::class, 'storePublicForm'])->name('leave.submit');
});
