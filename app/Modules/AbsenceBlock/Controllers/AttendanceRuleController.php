<?php

namespace App\Modules\AbsenceBlock\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRule;
use App\Modules\AbsenceBlock\Requests\SaveAttendanceRuleRequest;
use App\Modules\AbsenceBlock\Services\AbsenceBlockAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceRuleController extends Controller
{
    public function __construct(private readonly AbsenceBlockAudit $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AttendanceRule::class);

        return Inertia::render('backend/absence-blocks/Rules', [
            'rules' => AttendanceRule::query()
                ->with('creator:id,name')
                ->orderByDesc('id')
                ->get(),
            'canManage' => $request->user()->can('manage', AttendanceRule::class),
        ]);
    }

    public function store(SaveAttendanceRuleRequest $request): RedirectResponse
    {
        $this->authorize('manage', AttendanceRule::class);

        $rule = AttendanceRule::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $this->audit->log('attendance_rule.created', $request->user(), [
            'rule_id' => $rule->id,
            'after' => $rule->toArray(),
        ]);

        return back()->with('success', 'Attendance rule created.');
    }

    public function update(AttendanceRule $rule, SaveAttendanceRuleRequest $request): RedirectResponse
    {
        $this->authorize('manage', AttendanceRule::class);

        $before = $rule->toArray();
        $rule->update($request->validated());

        $this->audit->log('attendance_rule.updated', $request->user(), [
            'rule_id' => $rule->id,
            'before' => $before,
            'after' => $rule->fresh()->toArray(),
        ]);

        return back()->with('success', 'Attendance rule updated.');
    }

    public function toggle(AttendanceRule $rule, Request $request): RedirectResponse
    {
        $this->authorize('manage', AttendanceRule::class);

        $before = $rule->toArray();
        $rule->update(['is_active' => ! $rule->is_active]);

        $this->audit->log('attendance_rule.toggled', $request->user(), [
            'rule_id' => $rule->id,
            'before' => $before,
            'after' => $rule->fresh()->toArray(),
        ]);

        return back()->with('success', $rule->is_active ? 'Rule activated.' : 'Rule deactivated.');
    }

    public function destroy(AttendanceRule $rule, Request $request): RedirectResponse
    {
        $this->authorize('manage', AttendanceRule::class);

        // Log before deleting - activity_logs.rule_id is null-on-delete, so the
        // id is preserved in the "before" snapshot instead.
        $this->audit->log('attendance_rule.deleted', $request->user(), [
            'rule_id' => $rule->id,
            'before' => $rule->toArray(),
        ]);

        $rule->delete();

        return back()->with('success', 'Attendance rule deleted.');
    }
}
