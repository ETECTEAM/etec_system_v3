<?php

namespace App\Modules\Instructor\Services;

use App\Models\User;

/**
 * Central source of truth for the new-instructor onboarding gate.
 *
 * Only users created via self-registration (requires_onboarding = true) are
 * held out of the dashboard until they complete setup: select an employment
 * type, pick a work schedule, choose at least one specialization, and verify
 * a recovery email.
 */
class InstructorOnboardingService
{
    /**
     * True when the user has filled in every required onboarding field.
     */
    public function isComplete(User $user): bool
    {
        // Always read through the database: the relation may already be cached
        // on the model from earlier in the request (e.g. the profile update
        // loads it before saving), which would hide the just-persisted values.
        $user->unsetRelation('instructorData');
        $instructor = $user->instructorData;

        $profileComplete = $instructor
            && filled($instructor->employment_type)
            && filled($instructor->work_schedule_id)
            && ! empty($instructor->specialization);

        $recoveryComplete = (bool) ($user->recovery_verified && $user->recovery_email);

        return $profileComplete && $recoveryComplete;
    }

    /**
     * True when this user should be held out of the dashboard right now.
     */
    public function isPending(User $user): bool
    {
        if (! $user->requires_onboarding || $user->onboarding_completed_at) {
            return false;
        }

        // Completing setup and marking completion are decoupled (the timestamp
        // is only written when the setup form / recovery verify fires), so a
        // user whose fields are already complete but not yet stamped is treated
        // as done rather than gated.
        return ! $this->isComplete($user);
    }

    /**
     * Stamps onboarding_completed_at as soon as setup is actually complete.
     */
    public function markCompleteIfDone(User $user): void
    {
        if (! $user->requires_onboarding) {
            return;
        }

        if ($this->isComplete($user) && ! $user->onboarding_completed_at) {
            $user->forceFill(['onboarding_completed_at' => now()])->save();
        }
    }
}
