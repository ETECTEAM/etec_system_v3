<?php

use App\Modules\Certificate\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin|instructor'])
    ->prefix('dashboard/certificates')
    ->name('dashboard.certificates.')
    ->group(function (): void {
        Route::get('/', [CertificateController::class, 'index'])->name('index');
        Route::get('/classes', [CertificateController::class, 'classes'])->name('classes');
        Route::get('/report/classes', [CertificateController::class, 'reportClasses'])->name('report.classes');
        Route::get('/classes/{studyClass}/students', [CertificateController::class, 'students'])->name('students');
        Route::get('/generate-id', [CertificateController::class, 'generateId'])->name('generate-id');
        Route::post('/free', [CertificateController::class, 'storeFree'])->name('free.store');
        Route::post('/printed', [CertificateController::class, 'storePrinted'])->name('printed.store');
        Route::post('/courses', [CertificateController::class, 'saveCourse'])->name('courses.store');
        Route::delete('/courses', [CertificateController::class, 'deleteCourse'])->name('courses.destroy');
    });
