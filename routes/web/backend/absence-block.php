<?php

/*
|--------------------------------------------------------------------------
| Attendance Rules & Absence Block Routes
|--------------------------------------------------------------------------
|
| The school-office side of the absence-block workflow: rule CRUD, the
| blacklist (soft locks + hard locks), approvals, settings and the audit
| log. Open to admin + super_admin; the single super_admin-only action is
| clearing a hard lock. Mutations sit on a tighter throttle tier since each
| writes an activity_logs row.
|
*/

use App\Modules\AbsenceBlock\Controllers\AbsenceBlockAuditController;
use App\Modules\AbsenceBlock\Controllers\AbsenceBlockController;
use App\Modules\AbsenceBlock\Controllers\AttendanceRuleController;
use App\Modules\AbsenceBlock\Controllers\AttendanceRuleSettingController;
use App\Modules\AbsenceBlock\Controllers\BlacklistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/absence-blocks')
    ->name('absence-blocks.')
    ->group(function (): void {

        // Reads.
        Route::middleware('throttle:60,1')->group(function (): void {
            Route::get('/', [AbsenceBlockController::class, 'index'])->name('index');
            Route::get('/data', [AbsenceBlockController::class, 'data'])->name('data');
            Route::get('/rules', [AttendanceRuleController::class, 'index'])->name('rules.index');
            Route::get('/settings', [AttendanceRuleSettingController::class, 'edit'])->name('settings.edit');
            Route::get('/audit', [AbsenceBlockAuditController::class, 'index'])->name('audit.index');
            Route::get('/audit/data', [AbsenceBlockAuditController::class, 'data'])->name('audit.data');
        });

        // Mutations (each writes an audit row).
        Route::middleware('throttle:20,1')->group(function (): void {
            Route::post('/rules', [AttendanceRuleController::class, 'store'])->name('rules.store');
            Route::put('/rules/{rule}', [AttendanceRuleController::class, 'update'])->name('rules.update');
            Route::delete('/rules/{rule}', [AttendanceRuleController::class, 'destroy'])->name('rules.destroy');
            Route::patch('/rules/{rule}/toggle', [AttendanceRuleController::class, 'toggle'])->name('rules.toggle');

            Route::post('/blocks/{block}/approve', [AbsenceBlockController::class, 'approve'])->name('blocks.approve');
            Route::post('/blocks/{block}/reject', [AbsenceBlockController::class, 'reject'])->name('blocks.reject');

            Route::put('/settings', [AttendanceRuleSettingController::class, 'update'])->name('settings.update');

            // The sole super_admin-only capability.
            Route::middleware('role:super_admin')->group(function (): void {
                Route::post('/blocks/{block}/unlock', [BlacklistController::class, 'unlock'])->name('blocks.unlock');
            });
        });
    });
