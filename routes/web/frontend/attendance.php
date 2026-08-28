<?php

use App\Modules\Attendance\Controllers\AttendanceQrController;
use Illuminate\Support\Facades\Route;

// Route to render the public attendance scan page.
Route::get('/attendance/qr/{token}', [AttendanceQrController::class, 'show'])->name('frontend.attendance.qr.show');
// Route to submit a public attendance scan.
Route::post('/attendance/qr/{token}', [AttendanceQrController::class, 'store'])->middleware('throttle:attendance-qr-submit')->name('frontend.attendance.qr.store');
