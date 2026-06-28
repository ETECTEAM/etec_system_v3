<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Instructor\Requests\InstructorProfileRequest;
use App\Modules\Instructor\Services\InstructorProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstructorProfileController extends Controller
{
    public function __construct(
        private readonly InstructorProfileService $profileService,
    ) {}

    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('instructor'), 403);

        $instructorData = $request->user()?->instructorData;

        return Inertia::render('backend/instructors/Profile', [
            'instructorData' => $instructorData,
        ]);
    }

    public function update(InstructorProfileRequest $request): RedirectResponse
    {
        $this->profileService->saveProfile(
            $request->user()->id,
            $request->validated(),
        );

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
