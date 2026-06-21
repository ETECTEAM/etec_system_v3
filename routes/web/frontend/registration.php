<?php

use App\Modules\Registration\Controllers\StudentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/register', [StudentRegistrationController::class, 'create'])->name('frontend.register.create');
Route::post('/register', [StudentRegistrationController::class, 'store'])->name('frontend.register.store');