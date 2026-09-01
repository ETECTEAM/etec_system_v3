<?php

use App\Modules\Enroll\Controllers\CourseEnrollConfigController;
use App\Modules\Enroll\Controllers\EnrollmentClassController;
use Illuminate\Support\Facades\Route;

Route::prefix('/dashboard/enroll')->group(function (): void {
    // Enrollment Management: browsing, creating, and administering classes — super_admin and admin.
    Route::middleware(['auth', 'role:super_admin|admin'])->group(function (): void {
        Route::get('/', [EnrollmentClassController::class, 'index'])->name('enroll.index');

        // Route to list every course with its enrollment open/closed status and start date.
        Route::get('/config', [CourseEnrollConfigController::class, 'index'])->name('enroll.config.index');
        // Route to fetch paginated, searchable course enroll-config data for the config page's table.
        Route::get('/config/data', [CourseEnrollConfigController::class, 'data'])->name('enroll.config.data');
        // Route to set the same enrollment start date on every course at once.
        Route::post('/config/bulk-start-date', [CourseEnrollConfigController::class, 'bulkUpdateStartDate'])->name('enroll.config.bulk-start-date');
        // Route to set a course's display order (1 shows first) on the public student-register list.
        Route::put('/config/course/{course}/order', [CourseEnrollConfigController::class, 'updateCourseOrder'])->name('enroll.config.course-order');
        // Route to add a new enrollment schedule (time slot) for a course.
        Route::post('/config/{course}/schedules', [CourseEnrollConfigController::class, 'store'])->name('enroll.config.store');
        // Route to update an existing schedule's status/start date/prices.
        Route::put('/config/{config}', [CourseEnrollConfigController::class, 'update'])->name('enroll.config.update');
        // Route to remove a schedule (time slot) from a course.
        Route::delete('/config/{config}', [CourseEnrollConfigController::class, 'destroy'])->name('enroll.config.destroy');
        Route::get('/registrations/data', [EnrollmentClassController::class, 'publicRegistrations'])->name('enroll.registrations.data');
        // Route to edit a public registration's name/gender/phone from the Registrations tab.
        Route::put('/registrations/{enrollment}', [EnrollmentClassController::class, 'updateRegistration'])->name('enroll.registrations.update');
        // Route to list every open class for the "move to another class" / "assign to class" pickers.
        Route::get('/classes/select', [EnrollmentClassController::class, 'classesForSelect'])->name('enroll.classes.select');
        // Route to move a registered student into a different existing class, e.g. joining a friend's class -
        // also used to make the first class assignment for a registration RegisterStudentForSchedule parked
        // with no class at all (enrollment.study_class_id null - see no_room_and_instructor/no_instructor/no_room).
        Route::put('/registrations/{enrollment}/move', [EnrollmentClassController::class, 'moveRegistration'])->name('enroll.registrations.move');
        Route::get('/create', [EnrollmentClassController::class, 'create'])->name('enroll.create');
        Route::get('/view/{studyClass}', [EnrollmentClassController::class, 'show'])->name('enroll.show');
        Route::delete('/{studyClass}', [EnrollmentClassController::class, 'destroy'])->name('enroll.destroy');

        // Pre-register a student with no class yet — they're enrolled into one later.
        Route::get('/students/create', [EnrollmentClassController::class, 'createRegisteredStudent'])->name('enroll.students.create');
        Route::post('/students', [EnrollmentClassController::class, 'storeRegisteredStudent'])->name('enroll.students.store');
        Route::post('/{studyClass}/enrollments', [EnrollmentClassController::class, 'enroll'])->name('enroll.enrollments.store');
        Route::post('/enrollments/{enrollment}/deposit', [EnrollmentClassController::class, 'deposit'])->name('enroll.enrollments.deposit');
    });

    // Creating/editing/ending a class (and the cascading Building/Floor/Room + Course/Lesson
    // lookups its form needs): admins, or the instructor the class is assigned to — ownership
    // enforced in EnrollmentClassController::ensureInstructorOwnsClass(). These back the class
    // action menu, which instructors get on their dashboard for their own classes.
    Route::middleware(['auth', 'active', 'role:super_admin|admin|instructor'])->group(function (): void {
        // An instructor only reaches this by copying one of their own classes, so the
        // controller pins the copy to them rather than letting them assign a teacher.
        Route::post('/', [EnrollmentClassController::class, 'store'])->name('enroll.store');
        Route::get('/edit/{studyClass}', [EnrollmentClassController::class, 'edit'])->name('enroll.edit');
        // Pre-fill the create form with an existing class's values so it can be duplicated with a new term/time.
        Route::get('/copy/{studyClass}', [EnrollmentClassController::class, 'copy'])->name('enroll.copy');
        Route::put('/{studyClass}', [EnrollmentClassController::class, 'update'])->name('enroll.update');
        // Pre-End / End from the class action menu.
        Route::post('/{studyClass}/status', [EnrollmentClassController::class, 'updateStatus'])->name('enroll.status');
        // Inline capacity edit from the class card.
        Route::patch('/{studyClass}/capacity', [EnrollmentClassController::class, 'updateCapacity'])->name('enroll.capacity');

        // "Collapse Class": split one class between two instructors, each teaching their
        // own days (e.g. Code on Mon & Tue, Network on Wed & Thu).
        Route::get('/{studyClass}/instructors', [EnrollmentClassController::class, 'instructors'])->name('enroll.instructors.index');
        Route::post('/{studyClass}/instructors', [EnrollmentClassController::class, 'shareWithInstructor'])->name('enroll.instructors.store');
        Route::delete('/{studyClass}/instructors/{user}', [EnrollmentClassController::class, 'removeInstructor'])->name('enroll.instructors.destroy');

        Route::get('/buildings/{building}/floors', [EnrollmentClassController::class, 'floors'])->name('enroll.floors');
        Route::get('/floors/{floor}/rooms', [EnrollmentClassController::class, 'rooms'])->name('enroll.rooms');
        Route::get('/courses/{course}/lessons', [EnrollmentClassController::class, 'lessons'])->name('enroll.lessons');

        // Approval of QR registrations should be available to instructors and admins
        // on the class-management screens, not only super admins.
        Route::post('/enrollments/{enrollment}/approve', [EnrollmentClassController::class, 'approveEnrollment'])->name('enroll.enrollments.approve');
        Route::post('/enrollments/approve', [EnrollmentClassController::class, 'approveEnrollments'])->name('enroll.enrollments.approve-bulk');

        // Hand-register a walk-in student straight into a class (name/gender/phone
        // only) from the class list's action menu. Admins are unrestricted;
        // instructors are limited to their own classes in the controller. The
        // public QR self-registration flow is a separate path (frontend.class-join.*).
        Route::get('/{studyClass}/students/create', [EnrollmentClassController::class, 'createStudent'])->name('enroll.class-students.create');
        Route::post('/{studyClass}/students', [EnrollmentClassController::class, 'storeStudent'])
            ->middleware('throttle:20,1')
            ->name('enroll.class-students.store');
    });
});
