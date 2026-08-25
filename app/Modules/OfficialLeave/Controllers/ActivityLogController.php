<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only audit trail viewer (super_admin): who approved/rejected/revoked/deleted
 * what and when, with before/after snapshots and source IPs.
 */
class ActivityLogController extends Controller
{
    private const ACTIONS = [
        'qr.generated',
        'leave.submitted',
        'leave.approved',
        'leave.rejected',
        'leave.revoked',
        'leave.deleted',
        'settings.updated',
    ];

    public function index(): Response
    {
        return Inertia::render('backend/official-leaves/ActivityLog', [
            'actions' => self::ACTIONS,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $paginator = AuditLog::query()
            ->with(['user:id,name', 'officialLeave.student:id,full_name'])
            ->when($request->input('action'), fn ($q, $action) => $q->where('action', $action))
            ->when($request->input('user_id'), fn ($q, $userId) => $q->where('user_id', (int) $userId))
            ->when($request->input('date_from'), fn ($q, $from) => $q->whereDate('created_at', '>=', Carbon::parse($from)->toDateString()))
            ->when($request->input('date_to'), fn ($q, $to) => $q->whereDate('created_at', '<=', Carbon::parse($to)->toDateString()))
            ->orderByDesc('id')
            ->paginate(max(1, min(100, $request->integer('per_page', 15))));

        return response()->json([
            'data' => collect($paginator->items())->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->name ?? '(deleted user)',
                'user_id' => $log->user_id,
                'leave_id' => $log->official_leave_id,
                'student_name' => $log->officialLeave?->student?->full_name,
                'before' => $log->before,
                'after' => $log->after,
                'ip' => $log->ip,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }
}
