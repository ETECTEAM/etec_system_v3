<?php

use App\Modules\Floor\API\FloorApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:super_admin|admin|instructor'])
    ->prefix('floors')
    ->group(function (): void {
        Route::get('/', [FloorApiController::class, 'index']);
        Route::post('/', [FloorApiController::class, 'store']);
        Route::get('/{floor}', [FloorApiController::class, 'show']);
        Route::put('/{floor}', [FloorApiController::class, 'update']);
        Route::delete('/{floor}', [FloorApiController::class, 'destroy']);
    });
