<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Models\WorkSchedule;
use App\Modules\Account\Requests\UpdateRecoveryEmailRequest;
use App\Modules\Account\Services\RecoveryEmailService;
use App\Modules\Instructor\Requests\OnboardingTeachingRequest;
use App\Modules\Instructor\Services\InstructorOnboardingService;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Modules\Instructor\Services\InstructorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Drives the post-registration instructor onboarding wizard: the required
 * teaching setup (employment type, work schedule, specialization) and a
 * verified recovery email, in one guided flow instead of the full profile form
 * plus a separate trip to the Account Security page.
 */
class InstructorOnboardingController extends Controller
{
    private const REDIRECT = '/dashboard/instructor/onboarding';

    public function __construct(
        private readonly InstructorOnboardingService $onboarding,
        private readonly InstructorProfileService $profileService,
        private readonly RecoveryEmailService $recoveryEmail,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // The wizard's final "all set" screen is shown once, right after the
        // last step is finished (flagged by saveTeaching / verifyRecoveryEmail).
        // Any other visit by an already-onboarded instructor skips straight to
        // the dashboard.
        $justCompleted = (bool) $request->session()->pull('onboarding_just_completed', false);

        if (! $justCompleted && ! $this->onboarding->isPending($user)) {
            return redirect('/dashboard');
        }

        $instructor = $user->instructorData()->first();

        return Inertia::render('backend/instructors/Onboarding', [
            'user' => ['name' => $user->name, 'email' => $user->email],
            'instructorData' => $instructor ? [
                'employment_type' => $instructor->employment_type,
                'work_schedule_id' => $instructor->work_schedule_id,
                'specialization' => $this->normalizeSpecialization($instructor->specialization),
            ] : null,
            'justCompleted' => $justCompleted,
            'recoveryEmail' => $user->recovery_email,
            'recoveryVerified' => (bool) $user->recovery_verified,
            'workSchedules' => WorkSchedule::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description']),
            'subCategories' => SubCategory::where('status', 'active')
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function saveTeaching(OnboardingTeachingRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $instructor = $user->instructorData()->first();

        // saveProfile() owns every InstructorData column, so carry the untouched
        // fields through unchanged - the wizard only collects these three.
        $this->profileService->saveProfile($user->id, [
            'full_name' => $instructor?->full_name ?: $user->name,
            'instructor_code' => $instructor?->instructor_code ?: InstructorService::generateInstructorCode(),
            'phone' => $instructor?->phone,
            'specialization' => $data['specialization'],
            'employment_type' => $data['employment_type'],
            'work_schedule_id' => $data['work_schedule_id'],
            'headline' => $instructor?->headline,
            'bio' => $instructor?->bio,
            'date_of_birth' => $instructor?->date_of_birth?->format('Y-m-d'),
            'gender' => $instructor?->gender,
            'address' => $instructor?->address,
            'telegram' => $instructor?->telegram,
            'linkedin' => $instructor?->linkedin,
            'github' => $instructor?->github,
            'portfolio_url' => $instructor?->portfolio_url,
        ]);

        // A verified recovery email may already be on file, making this the
        // final step - stamp completion straight away if so.
        $fresh = $user->fresh();
        $this->onboarding->markCompleteIfDone($fresh);

        $redirect = redirect(self::REDIRECT)->with('success', 'Teaching setup saved.');

        if (! $this->onboarding->isPending($fresh)) {
            $redirect->with('onboarding_just_completed', true);
        }

        return $redirect;
    }

    public function saveRecoveryEmail(UpdateRecoveryEmailRequest $request): RedirectResponse
    {
        $user = $request->user();
        $sent = $this->recoveryEmail->updateAndSendVerification($user, $request->toData()->recoveryEmail);

        return redirect(self::REDIRECT)->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Verification link sent. Open it from that inbox to finish.'
                : 'Recovery email saved, but the link could not be sent right now. Use Resend in a moment.',
        );
    }

    public function resendRecoveryEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->recovery_email) {
            return redirect(self::REDIRECT)->with('error', 'Add a recovery email first.');
        }

        if ($user->recovery_verified) {
            return redirect(self::REDIRECT)->with('info', 'Your recovery email is already verified.');
        }

        $sent = $this->recoveryEmail->resendVerificationLink($user);

        return redirect(self::REDIRECT)->with(
            $sent ? 'success' : 'error',
            $sent ? 'Verification link resent.' : 'Could not resend the link right now. Please try again shortly.',
        );
    }

    // Polled by the wizard while it waits for the recovery-email link to be
    // clicked, so step 2 advances without the user reloading the page.
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'recoveryVerified' => (bool) $user->recovery_verified,
            'complete' => ! $this->onboarding->isPending($user),
        ]);
    }

    /**
     * Older instructor rows may still store specialization as a scalar or a
     * JSON string; always hand the frontend a plain array of strings.
     */
    private function normalizeSpecialization(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [$value];
        }

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
