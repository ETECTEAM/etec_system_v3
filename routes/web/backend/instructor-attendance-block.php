<?php

/*
|--------------------------------------------------------------------------
| Instructor Attendance Block Routes
|--------------------------------------------------------------------------
|
| Admin-facing review queue for instructors blocked after missing attendance
| on a class (see docs/instructor-attendance-block-proposal.md). Open to
| admin + super_admin - approving doesn't need the tighter super_admin-only
| tier used elsewhere (e.g. absence-block hard-lock).
|
| No reject action: an admin who doesn't want to unblock an instructor just
| doesn't click Approve - there's nothing a separate reject state would add.
|
*/

use App\Modules\Attendance\Controllers\InstructorAttendanceBlockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/instructor-attendance-blocks')
    ->name('instructor-attendance-blocks.')
    ->group(function (): void {
        // Route to show the admin review queue page.
        Route::get('/', [InstructorAttendanceBlockController::class, 'index'])->name('index');
        // Route to list instructor attendance blocks for the admin review queue.
        Route::get('/data', [InstructorAttendanceBlockController::class, 'data'])
            ->middleware('throttle:60,1')
            ->name('data');

        // Route to unblock an instructor after reviewing their request.
        Route::post('/{block}/approve', [InstructorAttendanceBlockController::class, 'approve'])
            ->middleware('throttle:20,1')
            ->name('approve');

        // Route to unblock an instructor whose leave/permission claim is accepted -
        // same unblock, plus a backfill of EVERY stuck session across their classes
        // (not just the triggering one). See docs/instructor-attendance-block-approve-after-permission.md.
        Route::post('/{block}/approve-after-permission', [InstructorAttendanceBlockController::class, 'approveAfterPermission'])
            ->middleware('throttle:20,1')
            ->name('approve-after-permission');
    });
