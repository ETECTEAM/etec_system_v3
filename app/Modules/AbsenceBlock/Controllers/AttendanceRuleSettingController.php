<?php

namespace App\Modules\AbsenceBlock\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRuleSetting;
use App\Models\StudentAttendanceBlock;
use App\Modules\AbsenceBlock\Requests\UpdateAttendanceRuleSettingsRequest;
use App\Modules\AbsenceBlock\Services\AbsenceBlockAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceRuleSettingController extends Controller
{
    public function __construct(private readonly AbsenceBlockAudit $audit) {}

    public function edit(): Response
    {
        $this->authorize('manageSettings', StudentAttendanceBlock::class);

        return Inertia::render('backend/absence-blocks/Settings', [
            'settings' => AttendanceRuleSetting::query()->orderBy('id')->get(
                ['key', 'value', 'type', 'label', 'description', 'min', 'max']
            ),
        ]);
    }

    public function update(UpdateAttendanceRuleSettingsRequest $request): RedirectResponse
    {
        $this->authorize('manageSettings', StudentAttendanceBlock::class);

        $data = $request->validated();
        $before = AttendanceRuleSetting::query()->pluck('value', 'key')->all();

        foreach ($data as $key => $value) {
            AttendanceRuleSetting::query()->where('key', $key)->update([
                'value' => (string) $value,
                'updated_by' => $request->user()->id,
            ]);
        }

        // Query-builder updates skip model events, so bust the cached read manually.
        Cache::forget(AttendanceRuleSetting::CACHE_KEY);

        $this->audit->log('attendance_rule_settings.updated', $request->user(), [
            'before' => $before,
            'after' => $data,
        ]);

        return back()->with('success', 'Attendance rule settings saved.');
    }
}
