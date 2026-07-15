<?php

/*
|--------------------------------------------------------------------------
| Backend Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based authentication flow for the backend
| interface, including login, registration, verification, and logout.
| Guest-only access and request throttling are applied where appropriate.
|
*/
use App\Modules\Auth\Controllers\AuthController; // Handles backend authentication actions.
use Illuminate\Support\Facades\Route; // Registers web routes for the application.

// Route to display the login page for guest users.
Route::middleware('guest')->get('/login', [AuthController::class, 'showLogin'])->name('login');

// Route to display the instructor registration screen for guests (kept off /register, which is the frontend student flow).
Route::middleware('guest')->get('/instructor-register', [AuthController::class, 'showRegister'])->name('register');

// Route to display the OTP verification page; reused by both registration and pending-status login, so no guest restriction here.
Route::get('/code-verify', [AuthController::class, 'showVerifyCode'])->name('code-verify');

// Route to process login form submissions; guest-only, with the 'login' rate limiter to slow brute-force attempts.
Route::middleware(['guest', 'throttle:login'])->post('/login', [AuthController::class, 'loginWeb'])->name('login.store');

// Route to process instructor registration submissions; guest-only, with the 'register' rate limiter to slow signup abuse.
Route::middleware(['guest', 'throttle:register'])->post('/instructor-register', [AuthController::class, 'registerWeb'])->name('register.store');

// Route to validate a submitted OTP code and activate the pending account; rate-limited to slow code-guessing.
Route::middleware('throttle:otp-verify')->post('/api/code-verify', [AuthController::class, 'verifyCodeApi'])->name('code-verify.store');

// Route to log the authenticated user out and invalidate their session.
Route::middleware('auth')->post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');
