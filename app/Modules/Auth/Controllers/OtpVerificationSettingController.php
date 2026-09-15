<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OtpVerificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OtpVerificationSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('backend/otp-settings/Edit', [
            'isEnabled' => OtpVerificationSetting::current()->is_enabled,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        OtpVerificationSetting::current()->update([
            'is_enabled' => $validated['is_enabled'],
        ]);

        return redirect()->route('otp-settings.edit')
            ->with('success', 'OTP verification setting updated successfully.');
    }
}
