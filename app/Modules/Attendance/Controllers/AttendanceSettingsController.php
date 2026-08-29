<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GradingSetting;
use App\Modules\Attendance\Queries\GetShortestClassDurationMinutes;
use App\Modules\Attendance\Requests\SaveAttendanceSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingsController extends Controller
{
    public function edit(GetShortestClassDurationMinutes $shortestDuration): Response
    {
        $rows = GradingSetting::query()
            ->where('group', 'attendance')
            ->get()
            ->keyBy('key');

        return Inertia::render('backend/attendance-settings/Edit', [
            'settings' => [
                'enabled' => $this->boolValue($rows, 'auto_record_enabled', true),
                'graceMinutes' => $this->numValue($rows, 'auto_record_grace_minutes', 15),
                'defaultStatus' => $this->stringValue($rows, 'auto_record_default_status', 'present'),
                'notifyInstructor' => $this->boolValue($rows, 'auto_record_notify_instructor', true),
                'allowOverride' => $this->boolValue($rows, 'auto_record_allow_override', true),
                'allowTrackAnytime' => $this->boolValue($rows, 'auto_record_allow_track_anytime', false),
                'overrideHours' => $this->numValue($rows, 'auto_record_override_hours', 24),
                'lockEnabled' => $this->boolValue($rows, 'lock_enabled', true),
                'hardLockEnabled' => $this->boolValue($rows, 'hard_lock_enabled', true),
                'absenceSoftLockThreshold' => $this->numValue($rows, 'absence_soft_lock_threshold', 3),
                'postApprovalAbsenceLimit' => $this->numValue($rows, 'post_approval_absence_limit', 2),
                'permissionLimit' => $this->numValue($rows, 'permission_limit', 1),
                'permissionPeriod' => $this->stringValue($rows, 'permission_period', 'week'),
                'cycleStartDate' => $this->stringValue($rows, 'cycle_start_date', now()->toDateString()),
                'officialPermissionExempt' => $this->boolValue($rows, 'official_permission_exempt', true),
            ],
            'shortestClassDurationMinutes' => $shortestDuration->handle(),
        ]);
    }

    public function update(SaveAttendanceSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // All-or-nothing: FormRequest has already rejected the whole payload if any
        // field failed, so every key below is known-valid before any row is touched.
        // Saved one model instance at a time (not a mass Builder::update()) so each save
        // fires GradingSetting's saved event and busts the setting() cache correctly.
        // $field is already "auto_record_..." (matches the request's own field names,
        // not the short suffix edit()'s read helpers use) - KEY_PREFIX doesn't apply here.
        foreach ($validated as $field => $value) {
            GradingSetting::query()->updateOrCreate(
                ['key' => 'attendance.'.$field],
                [
                    'value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
                    'type' => is_bool($value) ? 'boolean' : (is_int($value) ? 'number' : 'string'),
                    'label' => str($field)->replace(['auto_record_', 'lock_'], '')->replace('_', ' ')->title()->toString(),
                    'group' => 'attendance',
                    'updated_by' => $request->user()->id,
                ],
            );
        }

        return redirect()->route('attendance-settings.edit')
            ->with('success', 'Attendance settings updated successfully.');
    }

    private function boolValue($rows, string $key, bool $default): bool
    {
        $row = $rows->get('attendance.'.$key);

        return $row ? filter_var($row->value, FILTER_VALIDATE_BOOLEAN) : $default;
    }

    private function numValue($rows, string $key, int $default): int
    {
        $row = $rows->get('attendance.'.$key);

        return $row && is_numeric($row->value) ? (int) $row->value : $default;
    }

    private function stringValue($rows, string $key, string $default): string
    {
        $row = $rows->get('attendance.'.$key);

        return $row ? $row->value : $default;
    }
}
