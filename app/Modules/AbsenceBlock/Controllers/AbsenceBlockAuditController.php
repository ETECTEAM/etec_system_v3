<?php

namespace App\Modules\AbsenceBlock\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\StudentAttendanceBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The absence-block slice of the shared activity_logs trail: every row that
 * links to an attendance_rules row or a student_attendance_block row.
 */
class AbsenceBlockAuditController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAudit', StudentAttendanceBlock::class);

        return Inertia::render('backend/absence-blocks/AuditLog', [
            'logs' => $this->query(request()->only(['action', 'search', 'date_from', 'date_to'])),
            'filters' => request()->only(['action', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAudit', StudentAttendanceBlock::class);

        return response()->json($this->query($request->only(['action', 'search', 'date_from', 'date_to'])));
    }

    private function query(array $filters)
    {
        $query = ActivityLog::query()
            ->with(['user:id,name'])
            ->where(function ($q): void {
                $q->whereNotNull('rule_id')->orWhereNotNull('block_id');
            });

        if (! empty($filters['action'])) {
            $query->where('action', 'like', $filters['action'].'%');
        }

        if (! empty($filters['search'])) {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$filters['search']}%"));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest('id')->paginate(25)->withQueryString();
    }
}
