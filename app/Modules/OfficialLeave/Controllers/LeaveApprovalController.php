<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfficialLeave;
use App\Modules\OfficialLeave\Requests\RejectLeaveRequest;
use App\Modules\OfficialLeave\Requests\RevokeLeaveRequest;
use App\Modules\OfficialLeave\Services\AuditLogger;
use App\Modules\OfficialLeave\Services\LeavePresenterService;
use App\Modules\OfficialLeave\Services\OfficialLeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Leave history page plus the mutating row actions: approve, reject, revoke,
 * delete. All decisions funnel through OfficialLeaveService so overlap checks,
 * state guards, and audit logging stay in one place.
 */
class LeaveApprovalController extends Controller
{
    public function __construct(
        private readonly OfficialLeaveService $leaveService,
        private readonly LeavePresenterService $presenter,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function history(): Response
    {
        $classes = DB::table('study_classes')
            ->join('student_enrollments', 'student_enrollments.study_class_id', '=', 'study_classes.id')
            ->where('student_enrollments.enrollment_status', 'active')
            ->groupBy('study_classes.id', 'study_classes.title')
            ->orderBy('study_classes.title')
            ->get(['study_classes.id', 'study_classes.title']);

        return Inertia::render('backend/official-leaves/LeaveHistory', [
            'classes' => $classes->map(fn ($class) => [
                'id' => (int) $class->id,
                'title' => $class->title,
            ])->all(),
            'canDelete' => request()->user()?->hasRole('super_admin') ?? false,
        ]);
    }

    public function historyData(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'study_class_id' => $request->input('study_class_id'),
            'search' => $request->string('search')->toString(),
            'per_page' => max(1, min(100, $request->integer('per_page', 10))),
        ];

        $paginator = $this->presenter->historyQuery($filters);

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($leave) => $this->presenter->presentLeave($leave))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function approve(Request $request, OfficialLeave $official_leave): JsonResponse
    {
        $this->authorize('approve', $official_leave);

        $leave = $this->leaveService->approve($request->user(), $official_leave, $request->ip());

        return response()->json([
            'message' => "Approved leave #{$leave->id} for {$this->studentName($leave)}.",
            'data' => $this->presenter->presentLeave($leave),
        ]);
    }

    public function reject(RejectLeaveRequest $request, OfficialLeave $official_leave): JsonResponse
    {
        $leave = $this->leaveService->reject($request->user(), $official_leave, $request->validated('note'), $request->ip());

        return response()->json([
            'message' => "Rejected leave #{$leave->id}.",
            'data' => $this->presenter->presentLeave($leave),
        ]);
    }

    public function revoke(RevokeLeaveRequest $request, OfficialLeave $official_leave): JsonResponse
    {
        $leave = $this->leaveService->revoke($request->user(), $official_leave, $request->validated('note'), $request->ip());

        return response()->json([
            'message' => "Revoked leave #{$leave->id}. Attendance is editable again for those dates.",
            'data' => $this->presenter->presentLeave($leave),
        ]);
    }

    public function destroy(Request $request, OfficialLeave $official_leave): JsonResponse
    {
        $this->authorize('delete', $official_leave);

        $name = $this->studentName($official_leave);

        $this->leaveService->delete($request->user(), $official_leave, $request->ip());

        return response()->json([
            'message' => "Deleted leave #{$official_leave->id} for {$name}.",
        ]);
    }

    /**
     * CSV export honoring the same filters as historyData. Streams so large exports
     * don't build an array in memory.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'study_class_id' => $request->input('study_class_id'),
            'search' => $request->string('search')->toString(),
            'per_page' => 500,
        ];

        $filename = 'official-leaves-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['ID', 'Student', 'Class', 'Course', 'Start date', 'End date', 'Reason', 'Status', 'Requested at', 'Approved by']);

            $page = 1;

            do {
                $paginator = $this->presenter->historyQuery([...$filters, 'page' => $page]);
                $rows = collect($paginator->items())->map(fn ($leave) => $this->presenter->presentLeave($leave));

                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row['id'],
                        $row['student']['full_name'],
                        implode('; ', $row['classes']),
                        $row['course'] ?? '',
                        $row['start_date'],
                        $row['end_date'],
                        $row['reason'],
                        $row['status'],
                        $row['requested_at'],
                        $row['approved_by'] ?? '',
                    ]);
                }

                $page++;
            } while ($page <= $paginator->lastPage());

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function studentName(OfficialLeave $leave): string
    {
        return $leave->student?->full_name ?? "student #{$leave->student_id}";
    }
}
