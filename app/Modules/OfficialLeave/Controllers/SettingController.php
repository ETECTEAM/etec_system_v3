<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfficialLeaveSetting;
use App\Modules\OfficialLeave\Requests\UpdateOfficialLeaveSettingsRequest;
use App\Modules\OfficialLeave\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System-wide official-leave settings (super_admin). Values live in
 * official_leave_settings; config/official-leave.php only holds fallbacks.
 */
class SettingController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function edit(): Response
    {
        return Inertia::render('backend/official-leaves/Settings', [
            'settings' => $this->presentSettings(),
        ]);
    }

    public function update(UpdateOfficialLeaveSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rows = OfficialLeaveSetting::query()
            ->whereIn('key', array_keys($validated))
            ->get()
            ->keyBy('key');

        $before = $rows->map(fn ($row) => (int) $row->value)->all();

        // Saved one model at a time so each save fires the booted cache-bust.
        foreach ($validated as $key => $value) {
            $row = $rows->get($key);

            if (! $row) {
                continue;
            }

            $row->update([
                'value' => (string) max($row->min ?? 0, min($row->max ?? PHP_INT_MAX, $value)),
                'updated_by' => $request->user()->id,
            ]);
        }

        $after = OfficialLeaveSetting::query()
            ->whereIn('key', array_keys($validated))
            ->pluck('value', 'key')
            ->map(fn ($v) => (int) $v)
            ->all();

        $this->auditLogger->log(
            $request->user(),
            AuditLogger::ACTION_SETTINGS_UPDATED,
            null,
            $before,
            $after,
            $request->ip(),
        );

        return redirect()->route('official-leaves.settings.edit')
            ->with('success', 'Leave settings updated. Changes apply system-wide.');
    }

    private function presentSettings(): array
    {
        return OfficialLeaveSetting::query()
            ->where('group', 'official_leave')
            ->orderBy('id')
            ->get()
            ->map(fn (OfficialLeaveSetting $row) => [
                'key' => $row->key,
                'label' => $row->label,
                'description' => $row->description,
                'value' => (int) $row->value,
                'type' => $row->type,
                'min' => $row->min,
                'max' => $row->max,
                'updated_at' => $row->updated_at?->toIso8601String(),
            ])
            ->all();
    }
}
