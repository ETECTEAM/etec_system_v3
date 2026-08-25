<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OfficialLeave;
use App\Models\OfficialLeaveSetting;
use App\Modules\OfficialLeave\Requests\UpdateSettingRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        $this->authorize('manageSettings', OfficialLeave::class);

        $settings = OfficialLeaveSetting::orderBy('group')->orderBy('key')->get();

        return Inertia::render('backend/official-leaves/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $this->authorize('manageSettings', OfficialLeave::class);

        $data = $request->validated();

        foreach ($data as $key => $value) {
            $setting = OfficialLeaveSetting::where('key', $key)->first();

            if ($setting) {
                $before = $setting->value;
                $setting->update([
                    'value' => (string) $value,
                    'updated_by' => $request->user()->id,
                ]);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'setting_updated',
                    'before' => ['key' => $key, 'value' => $before],
                    'after' => ['key' => $key, 'value' => $value],
                    'ip_address' => $request->ip(),
                ]);
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
