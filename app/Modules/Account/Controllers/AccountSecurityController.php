<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Account\Notifications\RecoveryEmailChangedNotification;
use App\Modules\Account\Notifications\RecoveryEmailVerificationNotification;
use App\Modules\Account\Requests\UpdateRecoveryEmailRequest;
use App\Modules\Instructor\Services\InstructorOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Self-service recovery-email management, available to every logged-in
 * role - see LoginSecuritySettingsController for the separate, admin-only
 * global lockout config this is not part of.
 */
class AccountSecurityController extends Controller
{
    public function __construct(
        private readonly InstructorOnboardingService $onboarding,
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

        // Overwrites any prior recovery email outright and drops verification -
        // there is deliberately no staging column, so the account has zero
        // verified recovery email until the new link below is clicked.
        $user->forceFill([
            'recovery_email' => $data->recoveryEmail,
            'recovery_verified' => false,
        ])->save();

        // The email is saved either way - mail delivery failing here shouldn't
        // crash the request or lose the save, since "Resend" already exists
        // to retry once delivery is working again.
        if (! $this->sendVerificationLink($user)) {
            return redirect()->route('account-security.edit')
                ->with('error', 'Recovery email saved, but the verification email could not be sent right now. Use "Resend" to try again later.');
        }

        // Alert the login inbox, not the (unverified) new recovery address,
        // so a compromised recovery email alone can't silently take over the account.
        try {
            Notification::route('mail', $user->email)
                ->notify(new RecoveryEmailChangedNotification($data->recoveryEmail));
        } catch (Throwable $e) {
            Log::warning('Failed to send recovery-email-changed alert', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
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

        if (! $this->sendVerificationLink($user)) {
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

        // Verifying the recovery email may be the final onboarding step.
        $this->onboarding->markCompleteIfDone($account);

        if (Auth::check()) {
            return redirect()->route('account-security.edit')->with('success', 'Recovery email verified.');
        }

        return redirect()->route('login')->with('success', 'Recovery email verified. You can now sign in.');
    }

    private function sendVerificationLink(User $user): bool
    {
        $url = URL::temporarySignedRoute(
            'account-security.recovery-email.verify',
            now()->addHours(24),
            ['user' => $user->id]
        );

        try {
            Notification::route('mail', $user->recovery_email)
                ->notify(new RecoveryEmailVerificationNotification($url));

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to send recovery-email verification link', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
