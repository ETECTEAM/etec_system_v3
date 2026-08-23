<?php

use App\Modules\Room\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])->prefix('/dashboard/rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::get('/data', [RoomController::class, 'paginatedIndex']);
    Route::get('/create', [RoomController::class, 'create']);
    Route::get('/edit/{room}', [RoomController::class, 'edit']);
    Route::get('/{room}', [RoomController::class, 'show']);

    Route::post('/', [RoomController::class, 'store']);
    Route::post('/auto-generate', [RoomController::class, 'bulkStore']);
    Route::put('/{room}', [RoomController::class, 'update']);
    // Route to change a room's availability straight from the room list.
    Route::put('/{room}/status', [RoomController::class, 'updateStatus']);
    Route::delete('/{room}', [RoomController::class, 'destroy']);
});
