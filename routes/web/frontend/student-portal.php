<?php

use App\Http\Middleware\EnsureStudentPortalSession;
use App\Modules\Website\Controllers\StudentPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/student-portal/login', [StudentPortalController::class, 'login'])->name('frontend.student-portal.login');
Route::post('/student-portal/login', [StudentPortalController::class, 'authenticate'])->middleware('throttle:10,1')->name('frontend.student-portal.authenticate');
Route::get('/student-portal/forgot-code', [StudentPortalController::class, 'showForgotCode'])->name('frontend.student-portal.forgot-code');
Route::post('/student-portal/forgot-code', [StudentPortalController::class, 'sendForgotCode'])->middleware('throttle:5,1')->name('frontend.student-portal.forgot-code.send');
Route::get('/student-portal/recovery-email', [StudentPortalController::class, 'showRecoveryEmailSetup'])->name('frontend.student-portal.recovery-email.create');
Route::post('/student-portal/recovery-email', [StudentPortalController::class, 'storeRecoveryEmailSetup'])->middleware('throttle:5,1')->name('frontend.student-portal.recovery-email.store');
Route::middleware(EnsureStudentPortalSession::class)->group(function (): void {
    Route::get('/student-portal', [StudentPortalController::class, 'dashboard'])->name('frontend.student-portal.dashboard');
    Route::post('/student-portal/logout', [StudentPortalController::class, 'logout'])->name('frontend.student-portal.logout');
});
