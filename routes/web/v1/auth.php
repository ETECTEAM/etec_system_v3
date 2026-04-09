<?php

use App\Http\Controllers\Auth\AuthController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->get('/login', function () {
    return Inertia::render('auth/Login');
})->name('login');

Route::middleware('guest')->get('/register', function () {
    return Inertia::render('auth/Register');
})->name('register');

Route::middleware(['guest', 'throttle:login'])->post('/login', [AuthController::class, 'loginWeb'])->name('login.store');
Route::middleware('guest')->post('/register', [AuthController::class, 'registerWeb'])->name('register.store');
Route::middleware('auth')->post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');
