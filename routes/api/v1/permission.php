<?php

use App\Http\Controllers\Auth\PermissionManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'role:super_admin'])->group(function (): void {
    Route::post('/features', [PermissionManagementController::class, 'createFeaturePermissions']);
    Route::post('/roles', [PermissionManagementController::class, 'createRole']);
    Route::post('/permissions', [PermissionManagementController::class, 'createPermission']);
    Route::post('/roles/assign-permissions', [PermissionManagementController::class, 'assignPermissionsToRole']);
    Route::post('/users/{user}/assign-role', [PermissionManagementController::class, 'assignRoleToUser']);
    Route::post('/users/{user}/assign-permissions', [PermissionManagementController::class, 'assignPermissionsToUser']);
});
