<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

 Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('api.login');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login.store');
Route::prefix('auth')->group(function (): void {
   
});
