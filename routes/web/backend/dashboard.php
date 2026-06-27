<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return inertia('backend/Home');
        }

        if ($user->hasRole('instructor')) {
            return redirect()->route('dashboard.users.index');
        }

        abort(403);
    })->name('dashboard');
});