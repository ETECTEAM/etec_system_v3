<?php

use App\Modules\Attendance\Controllers\PreAttendanceRequestController;
use Illuminate\Support\Facades\Route;

// Admin review desk for instructor pre-attendance recovery requests.
Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/pre-attendance-requests')
    ->name('pre-attendance-requests.')
    ->group(function () {
        Route::get('/', [PreAttendanceRequestController::class, 'index'])->name('index');
        Route::put('/{preAttendanceRequest}', [PreAttendanceRequestController::class, 'update'])->middleware('throttle:20,1')->name('update');
    });

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/pre-attendance-classes')
    ->name('pre-attendance-classes.')
    ->group(function () {
        Route::get('/', [PreAttendanceRequestController::class, 'classes'])->name('index');
        Route::post('/{classSession}/approve', [PreAttendanceRequestController::class, 'approveClass'])->middleware('throttle:20,1')->name('approve');
    });

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/pre-attendance-counts')
    ->name('pre-attendance-counts.')
    ->group(function () {
        Route::get('/', [PreAttendanceRequestController::class, 'counts'])->name('index');
        Route::get('/instructors/{instructor}', [PreAttendanceRequestController::class, 'detail'])->name('detail');
    });
