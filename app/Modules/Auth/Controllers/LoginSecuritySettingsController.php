<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoginLockoutSetting;
use App\Models\LoginLockoutTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginSecuritySettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('backend/login-security/Edit', [
            'tiers' => LoginLockoutTier::query()
                ->orderBy('offense_number')
                ->get(['offense_number', 'duration_minutes']),
            'resetAfterHours' => LoginLockoutSetting::current()->reset_after_hours,
            'isEnabled' => LoginLockoutSetting::current()->is_enabled,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tiers' => ['required', 'array', 'min:1', 'max:20'],
            'tiers.*.duration_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'reset_after_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        // Replace-all: numbering is always the tier's position in the
        // submitted list, never trusted from the client directly.
        LoginLockoutTier::query()->delete();

        foreach (array_values($validated['tiers']) as $index => $tier) {
            LoginLockoutTier::create([
                'offense_number' => $index + 1,
                'duration_minutes' => $tier['duration_minutes'],
            ]);
        }

        LoginLockoutSetting::current()->update([
            'reset_after_hours' => $validated['reset_after_hours'],
            'is_enabled' => $validated['is_enabled'],
        ]);

        return redirect()->route('login-security.edit')
            ->with('success', 'Login security settings updated successfully.');
    }
}
