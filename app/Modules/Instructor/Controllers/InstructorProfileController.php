<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Instructor\Requests\InstructorProfileRequest;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Models\ShiftTemplate;
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
        abort_unless($request->user()?->can('instructor_profile.view'), 403);

        $instructorData = $request->user()?->instructorData;

        return Inertia::render('backend/instructors/Profile', [
            'instructorData' => $instructorData,
            'shiftTemplates' => ShiftTemplate::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function update(InstructorProfileRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('instructor_profile.update'), 403);

        $this->profileService->saveProfile(
            $request->user()->id,
            $request->validated(),
        );

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
