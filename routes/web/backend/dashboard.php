<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Services\DashboardReportService;
use App\Modules\Instructor\Services\InstructorClassService;
use App\Modules\Enroll\Actions\ActivateUpcomingClasses;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'onboarding', 'permission:dashboard.view'])->group(function () {
    Route::get('/dashboard', function (InstructorClassService $instructorClasses, ActivateUpcomingClasses $activate, DashboardReportService $dashboardReport) {
        $user = request()->user();

        if ($user->hasRole('instructor')) {
            $activate->handle();

            $instructorData = $user->instructorData()
                ->with(['profilePhoto', 'cvFile', 'attachments'])
                ->first();

            return inertia('backend/InstructorDashboard', [
                'instructorData' => $instructorData,
                'classes' => $instructorClasses->classes($user),
                'summary' => $instructorClasses->summary($user),
                'profilePhoto' => $instructorData?->profilePhoto,
                'cvFile' => $instructorData?->cvFile,
                'otherAttachments' => $instructorData?->attachments
                    ->whereNotIn('type', ['profile_photo', 'cv'])
                    ->values(),
            ]);
        }

        return inertia('backend/Home', [
            'report' => $dashboardReport->handle(request()),
        ]);
    })->name('dashboard');
});
