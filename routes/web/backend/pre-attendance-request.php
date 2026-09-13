<?php

use App\Modules\Attendance\Controllers\PreAttendanceRequestController;
use Illuminate\Support\Facades\Route;

// Admin review desk for instructor pre-attendance recovery requests. No separate
// "request queue" page: Pre-Att Class already lets an admin approve a class
// directly (PreAttendanceRequestController::approveClass), so there's nothing
// that flow adds beyond it.
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
