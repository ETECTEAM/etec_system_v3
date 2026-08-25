<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfficialLeave;
use App\Models\OfficialLeaveSetting;
use App\Modules\OfficialLeave\Requests\RejectLeaveRequest;
use App\Modules\OfficialLeave\Requests\RevokeLeaveRequest;
use App\Modules\OfficialLeave\Requests\StoreLeaveRequest;
use App\Modules\OfficialLeave\Services\LeaveApprovalService;
use App\Modules\OfficialLeave\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApprovalController extends Controller
{
    public function __construct(
        private readonly LeaveApprovalService $approvalService,
        private readonly LeaveRequestService $requestService
    ) {}

    public function history(): Response
    {
        $this->authorize('viewAny', OfficialLeave::class);

        return Inertia::render('backend/official-leaves/History');
    }

    public function historyData(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', OfficialLeave::class);

        $leaves = $this->approvalService->getHistory(
            $request->only(['status', 'search', 'start_date', 'end_date', 'class_id'])
        );

        return response()->json($leaves);
    }

    public function approve(OfficialLeave $official_leave): RedirectResponse
    {
        $this->authorize('approve', $official_leave);

        try {
            $this->approvalService->approve($official_leave, request()->user()->id);

            return back()->with('success', 'Leave approved successfully.');
        } catch (\Exception $e) {
            Log::error('Leave approval failed: ' . $e->getMessage());

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(OfficialLeave $official_leave, RejectLeaveRequest $request): RedirectResponse
    {
        $this->authorize('reject', $official_leave);

        try {
            $this->approvalService->reject(
                $official_leave,
                $request->user()->id,
                $request->note
            );

            return back()->with('success', 'Leave rejected.');
        } catch (\Exception $e) {
            Log::error('Leave rejection failed: ' . $e->getMessage());

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function revoke(OfficialLeave $official_leave, RevokeLeaveRequest $request): RedirectResponse
    {
        $this->authorize('revoke', $official_leave);

        try {
            $this->approvalService->revoke(
                $official_leave,
                $request->user()->id,
                $request->note
            );

            return back()->with('success', 'Leave revoked successfully.');
        } catch (\Exception $e) {
            Log::error('Leave revocation failed: ' . $e->getMessage());

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(OfficialLeave $official_leave): RedirectResponse
    {
        $this->authorize('delete', $official_leave);

        $this->approvalService->destroy($official_leave);

        return back()->with('success', 'Leave request deleted.');
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', OfficialLeave::class);

        $leaves = OfficialLeave::with(['student', 'approver'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->start_date, fn ($q) => $q->where('start_date', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->where('end_date', '<=', $request->end_date))
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leave_history_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leaves) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student', 'Start Date', 'End Date', 'Reason', 'Status', 'Approved By', 'Created At']);

            foreach ($leaves as $leave) {
                fputcsv($file, [
                    $leave->student->full_name ?? 'Unknown',
                    $leave->start_date->format('Y-m-d'),
                    $leave->end_date->format('Y-m-d'),
                    $leave->reason,
                    $leave->status,
                    $leave->approver->name ?? '-',
                    $leave->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
