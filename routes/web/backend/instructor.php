<?php

use App\Modules\Instructor\Controllers\InstructorClassController;
use App\Modules\Instructor\Controllers\InstructorProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:instructor'])->prefix('/dashboard/instructor')->group(function () {
    Route::get('/', [InstructorProfileController::class, 'show']);
    Route::get('/profile', [InstructorProfileController::class, 'edit']);
    Route::put('/profile', [InstructorProfileController::class, 'update'])->middleware('throttle:10,1');

    Route::get('/classes/create', [InstructorClassController::class, 'create'])->name('instructor.classes.create');
    Route::post('/classes', [InstructorClassController::class, 'store'])->name('instructor.classes.store');
    Route::get('/classes/{studyClass}', [InstructorClassController::class, 'show'])->name('instructor.classes.show');
    Route::get('/classes/{studyClass}/attendance', [InstructorClassController::class, 'attendance'])->name('instructor.classes.attendance');
    Route::get('/classes/{studyClass}/attendance/track', [InstructorClassController::class, 'trackAttendance'])->name('instructor.classes.attendance.track');
    Route::get('/classes/{studyClass}/attendance/students/{student}', [InstructorClassController::class, 'studentAttendance'])->name('instructor.classes.attendance.students.show');
    // Route to update a student profile from the instructor's class roster.
    Route::put('/classes/{studyClass}/students/{student}', [InstructorClassController::class, 'updateStudent'])->name('instructor.classes.students.update');
    // Route to move a student into another class by class ID.
    Route::put('/classes/{studyClass}/students/{student}/transfer', [InstructorClassController::class, 'transferStudent'])->name('instructor.classes.students.transfer');
    Route::post('/classes/{studyClass}/attendance', [InstructorClassController::class, 'storeAttendance'])->middleware('throttle:20,1')->name('instructor.classes.attendance.store');
    // Route to correct a session the system auto-recorded (see OverrideAttendanceRecord).
    Route::put('/classes/{studyClass}/attendance', [InstructorClassController::class, 'overrideAttendance'])->middleware('throttle:20,1')->name('instructor.classes.attendance.override');
});
