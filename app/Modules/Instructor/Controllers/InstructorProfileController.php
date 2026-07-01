<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Instructor\Requests\InstructorProfileRequest;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Models\ShiftTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'user' => $request->user(),
            'instructorData' => $instructorData,
            'shiftTemplates' => ShiftTemplate::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'employment_type']),
        ]);
    }

    public function update(InstructorProfileRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('instructor_profile.update'), 403);

        $data = $request->validated();
        $user = $request->user();
        $instructorData = $user->instructorData;

        $hasChanges = $data['email'] !== $user->email
            || !$instructorData
            || $data['full_name'] !== $instructorData->full_name
            || $data['phone'] !== $instructorData->phone
            || $data['employment_type'] !== $instructorData->employment_type
            || $data['shift_template_id'] != $instructorData->shift_template_id
            || !empty($data['password']);

        if (!$hasChanges) {
            return redirect()->back()->with('info', 'No changes to save.');
        }

        $user->update(['email' => $data['email']]);

        $this->profileService->saveProfile($user->id, $data);

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
