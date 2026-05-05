<?php

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->get('/dashboard', function () {
    return Inertia::render('backend/Home');
});

Route::middleware(['auth', 'role:super_admin|admin'])->get('/dashboard/notifications', function () {
    return Inertia::render('backend/notifications/Index');
});

Route::middleware(['auth', 'role:super_admin|admin'])->get('/dashboard/notifications/data', [NotificationController::class, 'index']);

Route::middleware('auth')->prefix('/dashboard/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/data', [UserController::class, 'paginatedIndex']);
    Route::get('/create', [UserController::class, 'create']);
    Route::get('/edit/{user}', [UserController::class, 'edit']);
    Route::get('/roles', [UserManagementController::class, 'roles']);
    Route::get('/permissions', [UserManagementController::class, 'permissions']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});
