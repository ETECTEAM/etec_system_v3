<?php

use App\Modules\EnRoll\Controllers\EnRollController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('/dashboard/students')->group(function (): void {
    Route::get('/', [EnRollController::class, 'index'])->name('students.index');
    Route::get('/create', [EnRollController::class, 'create'])->name('students.create');
    Route::post('/', [EnRollController::class, 'store'])->name('students.store');

    Route::get('/view/{studyClass}', [EnRollController::class, 'show'])->name('students.show');
    Route::get('/edit/{studyClass}', [EnRollController::class, 'edit'])->name('students.edit');
    Route::put('/{studyClass}', [EnRollController::class, 'update'])->name('students.update');

    Route::get('/buildings/{building}/floors', [EnRollController::class, 'floors'])->name('students.floors');
    Route::get('/floors/{floor}/rooms', [EnRollController::class, 'rooms'])->name('students.rooms');
    Route::get('/courses/{course}/lessons', [EnRollController::class, 'lessons'])->name('students.lessons');

    Route::get('/{studyClass}/students/create', [EnRollController::class, 'createStudent'])->name('students.class-students.create');
    Route::post('/{studyClass}/students', [EnRollController::class, 'storeStudent'])->name('students.class-students.store');
    Route::post('/{studyClass}/enrollments', [EnRollController::class, 'enroll'])->name('students.enrollments.store');
    Route::post('/enrollments/{enrollment}/deposit', [EnRollController::class, 'deposit'])->name('students.enrollments.deposit');
});
