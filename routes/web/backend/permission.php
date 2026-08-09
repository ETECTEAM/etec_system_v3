<?php

use App\Modules\Auth\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->get('/roles', [PermissionController::class, 'roles']);

Route::prefix('admin')->middleware(['auth', 'active', 'role:super_admin'])->group(function (): void {
    Route::post('/features', [PermissionController::class, 'createFeaturePermissions']);
    Route::post('/roles', [PermissionController::class, 'createRole']);
    Route::post('/permissions', [PermissionController::class, 'createPermission']);
    Route::post('/roles/assign-permissions', [PermissionController::class, 'assignPermissionsToRole']);
    Route::post('/users/{user}/assign-role', [PermissionController::class, 'assignRoleToUser']);
    Route::post('/users/{user}/assign-permissions', [PermissionController::class, 'assignPermissionsToUser']);
});
