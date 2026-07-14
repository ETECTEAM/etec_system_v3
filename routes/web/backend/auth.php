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

// Display the login screen for guest users only.
Route::middleware('guest')->get('/login', [AuthController::class, 'showLogin'])->name('login');

// Display the instructor registration screen for guest users only.
// Lives at /instructor-register because /register belongs to the
// frontend student registration flow.
Route::middleware('guest')->get('/instructor-register', [AuthController::class, 'showRegister'])->name('register');

// Display the verification page used after account registration.
Route::get('/code-verify', [AuthController::class, 'showVerifyCode'])->name('code-verify');

// Handle login form submission with guest restriction and rate limiting.
Route::middleware(['guest', 'throttle:login'])->post('/login', [AuthController::class, 'loginWeb'])->name('login.store');

// Handle new instructor registration requests.
Route::middleware(['guest', 'throttle:register'])->post('/instructor-register', [AuthController::class, 'registerWeb'])->name('register.store');

// Handle verification code submission for account activation.
Route::middleware('throttle:otp-verify')->post('/api/code-verify', [AuthController::class, 'verifyCodeApi'])->name('code-verify.store');

// Handle logout for authenticated users.
Route::middleware('auth')->post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');
