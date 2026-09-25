<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attendance\Queries\FindActiveInstructorAttendanceBlock;
use App\Models\InstructorAttendanceBlock;
use App\Modules\Dashboard\Services\DashboardReportService;
use App\Modules\Instructor\Services\InstructorClassService;
use App\Modules\Enroll\Actions\ActivateUpcomingClasses;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'onboarding', 'permission:dashboard.view'])->group(function () {
    Route::get('/dashboard', function (
        InstructorClassService $instructorClasses,
        ActivateUpcomingClasses $activate,
        DashboardReportService $dashboardReport,
        FindActiveInstructorAttendanceBlock $findActiveBlock,
    ) {
        $user = request()->user();

        if ($user->hasRole('instructor')) {
            $activate->handle();

            $instructorData = $user->instructorData()
                ->with(['profilePhoto', 'cvFile', 'attachments'])
                ->first();

            $attendanceBlock = $findActiveBlock->handle($user->id);

            return inertia('backend/InstructorDashboard', [
                'instructorData' => $instructorData,
                'classes' => $instructorClasses->classes($user),
                'summary' => $instructorClasses->summary($user),
                'profilePhoto' => $instructorData?->profilePhoto,
                'cvFile' => $instructorData?->cvFile,
                'otherAttachments' => $instructorData?->attachments
                    ->whereNotIn('type', ['profile_photo', 'cv'])
                    ->values(),
                // Attendance-blocked banner (see docs/instructor-attendance-block-proposal.md).
                'attendanceBlock' => $attendanceBlock ? [
                    'reason' => $attendanceBlock->reason,
                    'status' => $attendanceBlock->status,
                    'blocked_at' => $attendanceBlock->blocked_at?->toIso8601String(),
                    'pending_review' => $attendanceBlock->status === InstructorAttendanceBlock::STATUS_PENDING_REVIEW,
                    'triggered_study_class_id' => $attendanceBlock->triggeredBySession?->study_class_id,
                ] : null,
            ]);
        }

        return inertia('backend/Home', [
            // Revenue / payment figures are super_admin only; admins get enrollment data.
            'report' => $dashboardReport->handle(request(), $user->hasRole('super_admin')),
        ]);
    })->name('dashboard');
});
