<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Instructor\Requests\InstructorProfileRequest;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Models\WorkSchedule;
use App\Models\SubCategory;
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

    public function show(Request $request): Response
    {
        abort_unless($request->user()?->can('instructor_profile.view'), 403);

        $instructorData = $request->user()?->instructorData()
            ->with(['profilePhoto', 'cvFile', 'attachments', 'workSchedule'])
            ->first();

        return Inertia::render('backend/instructors/ShowProfile', [
            'instructorData' => $instructorData,
            'profilePhoto' => $instructorData?->profilePhoto,
            'cvFile' => $instructorData?->cvFile,
            'otherAttachments' => $instructorData?->attachments
                ->whereNotIn('type', ['profile_photo', 'cv'])
                ->values(),
            'workSchedule' => $instructorData?->workSchedule,
        ]);
    }

    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->can('instructor_profile.view'), 403);

        $instructorData = $request->user()?->instructorData()
            ->with(['profilePhoto', 'cvFile', 'attachments'])
            ->first();

        return Inertia::render('backend/instructors/Profile', [
            'user' => $request->user(),
            'instructorData' => $instructorData,
            'profilePhoto' => $instructorData?->profilePhoto,
            'cvFile' => $instructorData?->cvFile,
            'otherAttachments' => $instructorData?->attachments
                ->whereNotIn('type', ['profile_photo', 'cv'])
                ->values(),
            'workSchedules' => WorkSchedule::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description']),
            // Specialization options - instructors pick from the same sub-categories
            // courses are tagged with, so RegisterStudentForSchedule's specialization
            // matching (see bestFieldMatch()) has something exact to compare against.
            'subCategories' => SubCategory::where('status', 'active')
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function update(InstructorProfileRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('instructor_profile.update'), 403);

        $data = $request->validated();
        $user = $request->user();
        $instructorData = $user->instructorData;

        $hasFile = $request->hasFile('profile_photo') || $request->hasFile('cv_file') || $request->hasFile('attachments');

        $hasChanges = $hasFile
            || $data['email'] !== $user->email
            || !$instructorData
            || $data['full_name'] !== $instructorData->full_name
            || $data['phone'] !== $instructorData->phone
            || ($data['specialization'] ?? []) != ($instructorData->specialization ?? [])
            || $data['employment_type'] !== $instructorData->employment_type
            || ($data['work_schedule_id'] ?? null) != $instructorData->work_schedule_id
            || $data['headline'] !== $instructorData->headline
            || $data['bio'] !== $instructorData->bio
            || ($data['date_of_birth'] ?? null) !== ($instructorData->date_of_birth?->format('Y-m-d'))
            || $data['gender'] !== $instructorData->gender
            || $data['address'] !== $instructorData->address
            || $data['telegram'] !== $instructorData->telegram
            || $data['linkedin'] !== $instructorData->linkedin
            || $data['github'] !== $instructorData->github
            || $data['portfolio_url'] !== $instructorData->portfolio_url
            || !empty($data['password']);

        if (!$hasChanges) {
            return redirect()->back()->with('info', 'No changes to save.');
        }

        $user->update(['email' => $data['email']]);

        $instructor = $this->profileService->saveProfile($user->id, $data);

        if ($request->hasFile('profile_photo')) {
            $this->profileService->replaceAttachment(
                $instructor->id,
                $request->file('profile_photo'),
                'profile_photo',
                'Profile Photo',
            );
        }

        if ($request->hasFile('cv_file')) {
            $this->profileService->replaceAttachment(
                $instructor->id,
                $request->file('cv_file'),
                'cv',
                'CV',
            );
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->profileService->saveAttachment(
                    $instructor->id,
                    $file,
                    'other',
                    $file->getClientOriginalName(),
                );
            }
        }

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
