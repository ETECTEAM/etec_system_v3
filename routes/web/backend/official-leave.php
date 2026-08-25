<?php

use App\Modules\OfficialLeave\Controllers\ActivityLogController;
use App\Modules\OfficialLeave\Controllers\LeaveApprovalController;
use App\Modules\OfficialLeave\Controllers\LeaveRequestController;
use App\Modules\OfficialLeave\Controllers\ReportController;
use App\Modules\OfficialLeave\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Official-leave office flow: dashboard, QR request pipeline, history and decisions —
// admin (school office) + super_admin. Mutations sit on a tighter throttle tier than
// reads since every one of them writes an audit row.
Route::middleware(['auth', 'active', 'role:super_admin|admin'])
    ->prefix('/dashboard/official-leaves')
    ->name('official-leaves.')
    ->group(function (): void {
        // Reads: the desk page plus its JSON feeds.
        Route::middleware('throttle:60,1')->group(function (): void {
            Route::get('/', [LeaveRequestController::class, 'dashboard'])->name('dashboard');

            // Route to find students for the Request Leave search bar.
            Route::get('/students/search', [LeaveRequestController::class, 'searchStudents'])->name('students.search');

            // Route to poll a generated QR session until the phone submits (or it expires).
            Route::get('/sessions/{session}/poll', [LeaveRequestController::class, 'pollSession'])->name('sessions.poll');

            Route::get('/history', [LeaveApprovalController::class, 'history'])->name('history');
            Route::get('/history/data', [LeaveApprovalController::class, 'historyData'])->name('history.data');
            Route::get('/history/export', [LeaveApprovalController::class, 'exportCsv'])->name('history.export');
        });

        // Mutations: QR generation and every leave decision.
        Route::middleware('throttle:20,1')->group(function (): void {
            // Route to mint a single-use signed QR for a student's leave request.
            Route::post('/qr', [LeaveRequestController::class, 'generateQr'])->name('qr.generate');

            Route::post('/leaves/{official_leave}/approve', [LeaveApprovalController::class, 'approve'])->name('leaves.approve');
            Route::post('/leaves/{official_leave}/reject', [LeaveApprovalController::class, 'reject'])->name('leaves.reject');
            Route::post('/leaves/{official_leave}/revoke', [LeaveApprovalController::class, 'revoke'])->name('leaves.revoke');

            // Delete is rejected/pending rows only — gated to super_admin by policy.
            Route::delete('/leaves/{official_leave}', [LeaveApprovalController::class, 'destroy'])->name('leaves.destroy');
        });

        // Super-admin extras layered onto the same feature prefix.
        Route::middleware('role:super_admin')->group(function (): void {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports');
            Route::get('/reports/data', [ReportController::class, 'data'])->name('reports.data');

            Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

            Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
            Route::get('/activity-log/data', [ActivityLogController::class, 'data'])->name('activity-log.data');
        });
    });
