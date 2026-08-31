<?php

namespace App\Modules\AbsenceBlock\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendanceBlock;
use App\Modules\AbsenceBlock\Actions\ApproveAbsenceBlock;
use App\Modules\AbsenceBlock\Actions\RejectAbsenceBlock;
use App\Modules\AbsenceBlock\Requests\RejectAbsenceBlockRequest;
use App\Modules\AbsenceBlock\Services\BlacklistQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AbsenceBlockController extends Controller
{
    private const FILTER_KEYS = ['block_type', 'status', 'search', 'date_from', 'date_to'];

    public function __construct(private readonly BlacklistQuery $blacklist) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StudentAttendanceBlock::class);

        $filters = $request->only(self::FILTER_KEYS);

        return Inertia::render('backend/absence-blocks/Blacklist', [
            'filters' => $filters,
            'blocks' => $this->blacklist->paginate($filters),
            'canUnlock' => $request->user()->can('unlock', StudentAttendanceBlock::class),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudentAttendanceBlock::class);

        return response()->json($this->blacklist->paginate($request->only(self::FILTER_KEYS)));
    }

    public function approve(StudentAttendanceBlock $block, ApproveAbsenceBlock $action): RedirectResponse
    {
        $this->authorize('approve', StudentAttendanceBlock::class);

        $count = $action->handle($block, request()->user());

        return back()->with('success', "Absence block approved ({$count} record(s)). Post-approval allowance now applies.");
    }

    public function reject(StudentAttendanceBlock $block, RejectAbsenceBlockRequest $request, RejectAbsenceBlock $action): RedirectResponse
    {
        $this->authorize('reject', StudentAttendanceBlock::class);

        $count = $action->handle($block, $request->user(), $request->validated('admin_comment'));

        return back()->with('success', "Absence block cleared ({$count} record(s)). The student is unlocked.");
    }
}
