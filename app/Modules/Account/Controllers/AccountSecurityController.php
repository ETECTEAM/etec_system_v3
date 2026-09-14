<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Account\Requests\UpdateRecoveryEmailRequest;
use App\Modules\Account\Services\RecoveryEmailService;
use App\Modules\Instructor\Services\InstructorOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service recovery-email management, available to every logged-in
 * role - see LoginSecuritySettingsController for the separate, admin-only
 * global lockout config this is not part of.
 */
class AccountSecurityController extends Controller
{
    public function __construct(
        private readonly InstructorOnboardingService $onboarding,
        private readonly RecoveryEmailService $recoveryEmail,
    ) {}

    // Route to display the current recovery-email status for the logged-in user.
    public function edit(): Response
    {
        $user = Auth::user();

        return Inertia::render('backend/account-security/Edit', [
            'loginEmail' => $user->email,
            'recoveryEmail' => $user->recovery_email,
            'recoveryVerified' => $user->recovery_verified,
        ]);
    }

    // Route to submit a new/changed recovery email.
    public function updateRecoveryEmail(UpdateRecoveryEmailRequest $request): RedirectResponse
    {
        $data = $request->toData();
        $user = Auth::user();

        // The email is saved either way - mail delivery failing here shouldn't
        // crash the request or lose the save, since "Resend" already exists
        // to retry once delivery is working again.
        if (! $this->recoveryEmail->updateAndSendVerification($user, $data->recoveryEmail)) {
            return redirect()->route('account-security.edit')
                ->with('error', 'Recovery email saved, but the verification email could not be sent right now. Use "Resend" to try again later.');
        }

        return redirect()->route('account-security.edit')
            ->with('success', 'Recovery email saved. Check that inbox for a verification link.');
    }

    // Route to resend the verification link for an unverified recovery email.
    public function resendVerification(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->recovery_email) {
            return redirect()->route('account-security.edit')->with('error', 'Add a recovery email first.');
        }

        if ($user->recovery_verified) {
            return redirect()->route('account-security.edit')->with('error', 'Your recovery email is already verified.');
        }

        if (! $this->recoveryEmail->resendVerificationLink($user)) {
            return redirect()->route('account-security.edit')
                ->with('error', 'Could not send the verification email right now. Please try again shortly.');
        }

        return redirect()->route('account-security.edit')->with('success', 'Verification link resent.');
    }

    // Route to verify a recovery email from its signed link; guest-safe since the signature itself proves mailbox access.
    public function verifyRecoveryEmail(Request $request, int $user): RedirectResponse
    {
        $account = User::query()->findOrFail($user);
        $account->forceFill(['recovery_verified' => true])->save();

        // Was this account still working through the guided setup before this
        // click (which may itself complete it)?
        $wasOnboarding = $account->requires_onboarding && $account->onboarding_completed_at === null;

        // Verifying the recovery email may be the final onboarding step.
        $this->onboarding->markCompleteIfDone($account);

        if (Auth::check()) {
            // Someone finishing the onboarding wizard in the same session lands
            // back on it - the wizard forwards to the dashboard once every step
            // is done - rather than on the standalone Account Security page.
            if ($wasOnboarding && Auth::id() === $account->id) {
                return redirect('/dashboard/instructor/onboarding')
                    ->with('success', 'Recovery email verified.')
                    ->with('onboarding_just_completed', ! $this->onboarding->isPending($account));
            }

            return redirect()->route('account-security.edit')->with('success', 'Recovery email verified.');
        }

        return redirect()->route('login')->with('success', 'Recovery email verified. You can now sign in.');
    }
}
