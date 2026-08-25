<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfficialLeave;
use App\Modules\OfficialLeave\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewActivityLog', OfficialLeave::class);

        return Inertia::render('backend/official-leaves/ActivityLog');
    }

    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewActivityLog', OfficialLeave::class);

        $logs = $this->activityLogService->getLogs(
            $request->only(['action', 'user_id', 'start_date', 'end_date'])
        );

        return response()->json([
            'logs' => $logs,
            'filters' => $request->only(['action', 'user_id', 'start_date', 'end_date']),
        ]);
    }
}
