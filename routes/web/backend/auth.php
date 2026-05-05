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
use App\Http\Controllers\Auth\AuthController; // Handles backend authentication actions.
use Illuminate\Support\Facades\Route; // Registers web routes for the application.
use Inertia\Inertia; // Renders Inertia-powered frontend pages.

// Display the login screen for guest users only.
Route::middleware('guest')->get('/login', function () {
    return Inertia::render('auth/Login');
})->name('login');

// Display the registration screen for guest users only.
Route::middleware('guest')->get('/register', function () {
    return Inertia::render('auth/Register');
})->name('register');

// Display the verification page used after account registration.
Route::get('/code-verify', [AuthController::class, 'showVerifyCode'])->name('code-verify');

// Handle login form submission with guest restriction and rate limiting.
Route::middleware(['guest', 'throttle:login'])->post('/login', [AuthController::class, 'loginWeb'])->name('login.store');

// Handle new user registration requests.
Route::middleware('guest')->post('/register', [AuthController::class, 'registerWeb'])->name('register.store');

// Handle verification code submission for account activation.
Route::post('/api/code-verify', [AuthController::class, 'verifyCodeApi'])->name('code-verify.store');

// Handle logout for authenticated users.
Route::middleware('auth')->post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');
