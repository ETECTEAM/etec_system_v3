<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Account\Notifications\RecoveryEmailChangedNotification;
use App\Modules\Account\Notifications\RecoveryEmailVerificationNotification;
use App\Modules\Account\Requests\UpdateRecoveryEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service recovery-email management, available to every logged-in
 * role - see LoginSecuritySettingsController for the separate, admin-only
 * global lockout config this is not part of.
 */
class AccountSecurityController extends Controller
{
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

    // Route to submit a new/changed recovery email; requires the current password instead of a full re-auth.
    public function updateRecoveryEmail(UpdateRecoveryEmailRequest $request): RedirectResponse
    {
        $data = $request->toData();
        $user = Auth::user();

        if (! Hash::check($data->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password is incorrect.'],
            ]);
        }

        // Overwrites any prior recovery email outright and drops verification -
        // there is deliberately no staging column, so the account has zero
        // verified recovery email until the new link below is clicked.
        $user->forceFill([
            'recovery_email' => $data->recoveryEmail,
            'recovery_verified' => false,
        ])->save();

        $this->sendVerificationLink($user);

        // Alert the login inbox, not the (unverified) new recovery address,
        // so a compromised recovery email alone can't silently take over the account.
        Notification::route('mail', $user->email)
            ->notify(new RecoveryEmailChangedNotification($data->recoveryEmail));

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

        $this->sendVerificationLink($user);

        return redirect()->route('account-security.edit')->with('success', 'Verification link resent.');
    }

    // Route to verify a recovery email from its signed link; guest-safe since the signature itself proves mailbox access.
    public function verifyRecoveryEmail(Request $request, int $user): RedirectResponse
    {
        $account = User::query()->findOrFail($user);
        $account->forceFill(['recovery_verified' => true])->save();

        if (Auth::check()) {
            return redirect()->route('account-security.edit')->with('success', 'Recovery email verified.');
        }

        return redirect()->route('login')->with('success', 'Recovery email verified. You can now sign in.');
    }

    private function sendVerificationLink(User $user): void
    {
        $url = URL::temporarySignedRoute(
            'account-security.recovery-email.verify',
            now()->addHours(24),
            ['user' => $user->id]
        );

        Notification::route('mail', $user->recovery_email)
            ->notify(new RecoveryEmailVerificationNotification($url));
    }
}
