<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Instructor\Services\InstructorClassService;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'permission:dashboard.view'])->group(function () {
    Route::get('/dashboard', function (InstructorClassService $instructorClasses) {
        $user = request()->user();

        if ($user->hasRole('instructor')) {
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

        return inertia('backend/Home');
    })->name('dashboard');
});
