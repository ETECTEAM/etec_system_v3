<?php

use App\Modules\StudentManagement\Controllers\StudentManagementController;
use Illuminate\Support\Facades\Route;

// Super-admin-only student governance, lock review, and correction tools.
Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/student-management')
    ->name('student-management.')
    ->group(function () {
        // Route to display the master student list.
        Route::get('/students', [StudentManagementController::class, 'students'])->name('students');

        // Admin-managed finalized attendance corrections.
        Route::post('/students/{student}/permission', [StudentManagementController::class, 'grantPermission'])->name('students.permission');
        Route::post('/students/{student}/transfer', [StudentManagementController::class, 'transferClass'])->name('students.transfer');
        Route::post('/students/{student}/late', [StudentManagementController::class, 'markLate'])->name('students.late');

        // Route to display soft-lock workflow rows.
        Route::get('/locks', [StudentManagementController::class, 'locks'])->name('locks');

        // Route to display hard-lock workflow rows.
        Route::get('/hard-locks', [StudentManagementController::class, 'hardLocks'])->name('hard-locks');
    });
