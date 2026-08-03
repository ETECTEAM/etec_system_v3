<?php

use App\Modules\Enroll\Controllers\EnrollmentClassController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('/dashboard/students')->group(function (): void {
    Route::get('/', [EnrollmentClassController::class, 'index'])->name('students.index');
    Route::get('/create', [EnrollmentClassController::class, 'create'])->name('students.create');
    Route::post('/', [EnrollmentClassController::class, 'store'])->name('students.store');

    Route::get('/view/{studyClass}', [EnrollmentClassController::class, 'show'])->name('students.show');
    Route::get('/edit/{studyClass}', [EnrollmentClassController::class, 'edit'])->name('students.edit');
    Route::put('/{studyClass}', [EnrollmentClassController::class, 'update'])->name('students.update');
    Route::delete('/{studyClass}', [EnrollmentClassController::class, 'destroy'])->name('students.destroy');
    Route::post('/{studyClass}/status', [EnrollmentClassController::class, 'updateStatus'])->name('students.status');

    Route::get('/buildings/{building}/floors', [EnrollmentClassController::class, 'floors'])->name('students.floors');
    Route::get('/floors/{floor}/rooms', [EnrollmentClassController::class, 'rooms'])->name('students.rooms');
    Route::get('/courses/{course}/lessons', [EnrollmentClassController::class, 'lessons'])->name('students.lessons');

    Route::get('/{studyClass}/students/create', [EnrollmentClassController::class, 'createStudent'])->name('students.class-students.create');
    Route::post('/{studyClass}/students', [EnrollmentClassController::class, 'storeStudent'])->name('students.class-students.store');
    Route::post('/{studyClass}/enrollments', [EnrollmentClassController::class, 'enroll'])->name('students.enrollments.store');
    Route::post('/enrollments/{enrollment}/deposit', [EnrollmentClassController::class, 'deposit'])->name('students.enrollments.deposit');
});
