<?php

use App\Http\Controllers\Admin\UserManagementController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'))) {
        return redirect('/dashboard');
    }

    return Inertia::render('home/Home');
});

Route::middleware('auth')->get('/dashboard', function () {
    return Inertia::render('backend/Home');
});

Route::middleware('auth')->prefix('/dashboard/users')->group(function () {
    Route::get('/', [UserManagementController::class, 'index']);
    Route::get('/create', [UserManagementController::class, 'create']);
    Route::get('/roles', [UserManagementController::class, 'roles']);
    Route::get('/permissions', [UserManagementController::class, 'permissions']);
    Route::post('/', [UserManagementController::class, 'store']);
});

Route::get('/{any}', function () {
    return Inertia::render('home/Home');
})->where('any', '.*');
