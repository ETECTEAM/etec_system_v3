<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudyClass;
use App\Models\SubCategory;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleTime;
use App\Modules\Instructor\Requests\InstructorProfileRequest;
use App\Modules\Instructor\Services\InstructorOnboardingService;
use App\Modules\Instructor\Services\InstructorProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InstructorProfileController extends Controller
{
    public function __construct(
        private readonly InstructorProfileService $profileService,
        private readonly InstructorOnboardingService $onboarding,
    ) {}

    public function show(Request $request): Response
    {
        abort_unless($request->user()?->can('instructor_profile.view'), 403);

        $instructorData = $request->user()?->instructorData()
            ->with(['profilePhoto', 'cvFile', 'attachments', 'workSchedule.times.time'])
            ->first();

        return Inertia::render('backend/instructors/ShowProfile', [
            'instructorData' => $instructorData,
            'profilePhoto' => $instructorData?->profilePhoto,
            'cvFile' => $instructorData?->cvFile,
            'otherAttachments' => $instructorData?->attachments
                ->whereNotIn('type', ['profile_photo', 'cv'])
                ->values(),
            'workSchedule' => $this->presentWorkSchedule($instructorData?->workSchedule),
        ]);
    }

    /**
     * The chosen work schedule as a day-by-day list of time slots, for the profile page.
     *
     * @return array{name: string, description: ?string, days: list<array{day: string, times: list<string>}>}|null
     */
    private function presentWorkSchedule(?WorkSchedule $workSchedule): ?array
    {
        if ($workSchedule === null) {
            return null;
        }

        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        return [
            'name' => $workSchedule->name,
            'description' => $workSchedule->description,
            'days' => $workSchedule->times
                ->groupBy('day_of_week')
                ->sortKeys()
                ->map(fn ($entries, $day): array => [
                    'day' => $dayNames[$day] ?? (string) $day,
                    // Chronological, not the string sort a database returns ("02:00 pm" would come before "09:00 am").
                    'times' => $entries
                        ->map(fn (WorkScheduleTime $entry) => $entry->time?->time_name)
                        ->filter()
                        ->sortBy(fn (string $name) => StudyClass::parseTimeRange($name)['start'] ?? '99:99')
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->can('instructor_profile.view'), 403);

        $instructorData = $request->user()?->instructorData()
            ->with(['profilePhoto', 'cvFile', 'attachments'])
            ->first();

        $onboardingPending = $request->user()
            ? $this->onboarding->isPending($request->user())
            : false;

        return Inertia::render('backend/instructors/Profile', [
            'user' => $request->user(),
            'instructorData' => $instructorData,
            'onboardingPending' => $onboardingPending,
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

        $hasChanges = $data['email'] !== $user->email
            || ! $instructorData
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
            || ! empty($data['password']);

        if (! $hasChanges) {
            return redirect()->back()->with('info', 'No changes to save.');
        }

        $user->update(['email' => $data['email']]);

        $instructor = $this->profileService->saveProfile($user->id, $data);

        // FILE: disabled - not using file uploads
        // if ($request->hasFile('profile_photo')) {
        //     $this->profileService->replaceAttachment(
        //         $instructor->id,
        //         $request->file('profile_photo'),
        //         'profile_photo',
        //         'Profile Photo',
        //     );
        // }

        // if ($request->hasFile('cv_file')) {
        //     $this->profileService->replaceAttachment(
        //         $instructor->id,
        //         $request->file('cv_file'),
        //         'cv',
        //         'CV',
        //     );
        // }

        // if ($request->hasFile('attachments')) {
        //     foreach ($request->file('attachments') as $file) {
        //         $this->profileService->saveAttachment(
        //             $instructor->id,
        //             $file,
        //             'other',
        //             $file->getClientOriginalName(),
        //         );
        //     }
        // }

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        // Once the profile carries the required fields, let this self-registered
        // instructor through the onboarding gate.
        $this->onboarding->markCompleteIfDone($user);

        // If they saved a now-complete profile from this page but still owe a
        // verified recovery email, hand them straight to that step of the
        // guided setup instead of leaving them on the form.
        if ($this->onboarding->isPending($user->fresh())) {
            return redirect('/dashboard/instructor/onboarding')
                ->with('success', 'Profile saved. One more step: verify a recovery email.');
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    // FILE: disabled - not using file uploads
    // public function destroyAttachment(Request $request, string $type): RedirectResponse
    // {
    //     abort_unless($request->user()?->can('instructor_profile.update'), 403);
    //
    //     $attachmentType = match ($type) {
    //         'profile-photo' => 'profile_photo',
    //         'cv' => 'cv',
    //         default => null,
    //     };
    //
    //     if (! $attachmentType) {
    //         abort(404);
    //     }
    //
    //     $instructorData = $request->user()->instructorData;
    //
    //     if (! $instructorData) {
    //         abort(404);
    //     }
    //
    //     $deleted = $this->profileService->deleteAttachment(
    //         $instructorData->id,
    //         $attachmentType,
    //     );
    //
    //     if (! $deleted) {
    //         return redirect()->back()->with('info', 'No file found to delete.');
    //     }
    //
    //     return redirect()->back()->with('success', 'File deleted successfully.');
    // }
}
