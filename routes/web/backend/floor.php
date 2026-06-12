<?php

use App\Modules\Floor\Controller\FloorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super_admin|admin|instructor'])->prefix('/dashboard/floors')->group(function (): void {
    Route::get('/', [FloorController::class, 'index']);
    Route::get('/data', [FloorController::class, 'paginatedIndex']);
    Route::get('/create', [FloorController::class, 'create']);
    Route::get('/edit/{floor}', [FloorController::class, 'edit']);
    Route::post('/', [FloorController::class, 'store']);
    Route::get('/{floor}', [FloorController::class, 'show']);
    Route::put('/{floor}', [FloorController::class, 'update']);
    Route::delete('/{floor}', [FloorController::class, 'destroy']);
});
