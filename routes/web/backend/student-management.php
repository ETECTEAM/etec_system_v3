<?php

use App\Modules\StudentManagement\Controllers\StudentManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])->prefix('/dashboard/student-management')->name('student-management.')->group(function (): void {
    Route::get('/', [StudentManagementController::class, 'index'])->name('index');
    Route::get('/enrollments/{enrollment}/attendance', [StudentManagementController::class, 'attendance'])->name('enrollments.attendance');
    Route::put('/students/{student}', [StudentManagementController::class, 'update'])->name('students.update');
    Route::post('/students/{student}/permission', [StudentManagementController::class, 'permission'])->name('students.permission');
    Route::put('/enrollments/{enrollment}/transfer', [StudentManagementController::class, 'transfer'])->name('enrollments.transfer');
    Route::post('/enrollments/{enrollment}/late', [StudentManagementController::class, 'late'])->name('enrollments.late');
    Route::delete('/enrollments/{enrollment}', [StudentManagementController::class, 'destroy'])->name('enrollments.destroy');
});
