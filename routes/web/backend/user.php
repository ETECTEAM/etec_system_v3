<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
