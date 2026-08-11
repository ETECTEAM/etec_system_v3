<?php

use App\Modules\building\Controller\BuildingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/buildings')
    ->group(function (): void {
        Route::get('/', [BuildingController::class, 'index']);
        Route::get('/create', [BuildingController::class, 'create']);
        Route::get('/edit/{building}', [BuildingController::class, 'edit']);
        Route::post('/', [BuildingController::class, 'store']);
        Route::put('/{building}', [BuildingController::class, 'update']);
        Route::delete('/{building}', [BuildingController::class, 'destroy']);

        Route::post('/{building}/floors', [BuildingController::class, 'storeFloor']);
        Route::post('/{building}/floors/auto-generate', [BuildingController::class, 'bulkStoreFloor']);
        Route::put('/{building}/floors/{floor}', [BuildingController::class, 'updateFloor']);
        Route::delete('/{building}/floors/{floor}', [BuildingController::class, 'destroyFloor']);

        Route::post('/{building}/floors/{floor}/rooms', [BuildingController::class, 'storeRoom']);
        Route::put('/{building}/floors/{floor}/rooms/{room}', [BuildingController::class, 'updateRoom']);
        Route::delete('/{building}/floors/{floor}/rooms/{room}', [BuildingController::class, 'destroyRoom']);
    });
