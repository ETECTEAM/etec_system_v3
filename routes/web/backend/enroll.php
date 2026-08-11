<?php

use App\Modules\Enroll\Controllers\CourseEnrollConfigController;
use App\Modules\Enroll\Controllers\EnrollmentClassController;
use Illuminate\Support\Facades\Route;

Route::prefix('/dashboard/enroll')->group(function (): void {
    // Enrollment Management: browsing, creating, and administering classes — super_admin only.
    Route::middleware(['auth', 'role:super_admin'])->group(function (): void {
        Route::get('/', [EnrollmentClassController::class, 'index'])->name('enroll.index');

        // Route to list every course with its enrollment open/closed status and start date.
        Route::get('/config', [CourseEnrollConfigController::class, 'index'])->name('enroll.config.index');
        // Route to fetch paginated, searchable course enroll-config data for the config page's table.
        Route::get('/config/data', [CourseEnrollConfigController::class, 'data'])->name('enroll.config.data');
        // Route to set the same enrollment start date on every course at once.
        Route::post('/config/bulk-start-date', [CourseEnrollConfigController::class, 'bulkUpdateStartDate'])->name('enroll.config.bulk-start-date');
        // Route to set a single course's enrollment status/start date.
        Route::put('/config/{course}', [CourseEnrollConfigController::class, 'update'])->name('enroll.config.update');
        Route::get('/registrations/data', [EnrollmentClassController::class, 'publicRegistrations'])->name('enroll.registrations.data');
        // Route to edit a public registration's name/gender/phone from the Registrations tab.
        Route::put('/registrations/{enrollment}', [EnrollmentClassController::class, 'updateRegistration'])->name('enroll.registrations.update');
        Route::get('/create', [EnrollmentClassController::class, 'create'])->name('enroll.create');
        Route::post('/', [EnrollmentClassController::class, 'store'])->name('enroll.store');
        Route::get('/view/{studyClass}', [EnrollmentClassController::class, 'show'])->name('enroll.show');
        Route::delete('/{studyClass}', [EnrollmentClassController::class, 'destroy'])->name('enroll.destroy');

        // Pre-register a student with no class yet — they're enrolled into one later.
        Route::get('/students/create', [EnrollmentClassController::class, 'createRegisteredStudent'])->name('enroll.students.create');
        Route::post('/students', [EnrollmentClassController::class, 'storeRegisteredStudent'])->name('enroll.students.store');
        Route::post('/{studyClass}/status', [EnrollmentClassController::class, 'updateStatus'])->name('enroll.status');
        Route::post('/{studyClass}/enrollments', [EnrollmentClassController::class, 'enroll'])->name('enroll.enrollments.store');
        Route::post('/enrollments/{enrollment}/deposit', [EnrollmentClassController::class, 'deposit'])->name('enroll.enrollments.deposit');
    });

    // Editing a class (and the cascading Building/Floor/Room + Course/Lesson lookups its
    // form needs): super_admin, or the instructor the class is assigned to — enforced in
    // EnrollmentClassController::ensureInstructorOwnsClass().
    Route::middleware(['auth'])->group(function (): void {
        Route::get('/edit/{studyClass}', [EnrollmentClassController::class, 'edit'])->name('enroll.edit');
        // Pre-fill the create form with an existing class's values so it can be duplicated with a new term/time.
        Route::get('/copy/{studyClass}', [EnrollmentClassController::class, 'copy'])->name('enroll.copy');
        Route::put('/{studyClass}', [EnrollmentClassController::class, 'update'])->name('enroll.update');

        Route::get('/buildings/{building}/floors', [EnrollmentClassController::class, 'floors'])->name('enroll.floors');
        Route::get('/floors/{floor}/rooms', [EnrollmentClassController::class, 'rooms'])->name('enroll.rooms');
        Route::get('/courses/{course}/lessons', [EnrollmentClassController::class, 'lessons'])->name('enroll.lessons');
    });

    // Self-registration via the "Generate QR" link an instructor shares with a prospective
    // student: no ETEC account or login required — the student fills this in themselves,
    // whenever they choose, and CreateClassStudent creates their account + enrollment.
    Route::get('/{studyClass}/students/create', [EnrollmentClassController::class, 'createStudent'])->name('enroll.class-students.create');
    Route::post('/{studyClass}/students', [EnrollmentClassController::class, 'storeStudent'])->name('enroll.class-students.store');
});
