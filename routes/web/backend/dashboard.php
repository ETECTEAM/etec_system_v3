<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'permission:dashboard.view'])->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();

        if ($user->hasRole('instructor')) {
            $instructorData = $user->instructorData()
                ->with(['profilePhoto', 'cvFile', 'attachments', 'shiftTemplate'])
                ->first();

            return inertia('backend/InstructorDashboard', [
                'instructorData' => $instructorData,
                'profilePhoto' => $instructorData?->profilePhoto,
                'cvFile' => $instructorData?->cvFile,
                'otherAttachments' => $instructorData?->attachments
                    ->whereNotIn('type', ['profile_photo', 'cv'])
                    ->values(),
                'shiftTemplate' => $instructorData?->shiftTemplate,
            ]);
        }

        return inertia('backend/Home');
    })->name('dashboard');
});
