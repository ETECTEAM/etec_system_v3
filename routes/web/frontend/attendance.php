<?php

use App\Modules\Attendance\Controllers\AttendanceQrController;
use App\Modules\Enroll\Controllers\PublicStudentAttendanceController;
use Illuminate\Support\Facades\Route;

// Route to render the public attendance scan page.
Route::get('/attendance/qr/{token}', [AttendanceQrController::class, 'show'])->name('frontend.attendance.qr.show');
// Route to submit a public attendance scan.
Route::post('/attendance/qr/{token}', [AttendanceQrController::class, 'store'])->middleware('throttle:attendance-qr-submit')->name('frontend.attendance.qr.store');

// Public, read-only attendance summary a family member reaches by scanning the QR code on a student's enrolment receipt.
Route::get('/student-attendance/{enrollment:public_token}', [PublicStudentAttendanceController::class, 'show'])->middleware('throttle:60,1')->name('frontend.student-attendance.show');
