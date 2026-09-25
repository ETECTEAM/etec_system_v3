<?php

use App\Modules\Attendance\Controllers\AttendanceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin'])
    ->prefix('/dashboard/attendance-report')
    ->name('attendance-report.')
    ->group(function (): void {
        Route::get('/', [AttendanceReportController::class, 'index'])->name('index');
    });
