<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OfficialLeave\Services\OfficialLeaveReportService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Super-admin reports & stats for the official-leave feature.
 */
class ReportController extends Controller
{
    public function __construct(private readonly OfficialLeaveReportService $reports) {}

    public function index(): Response
    {
        return Inertia::render('backend/official-leaves/Reports', [
            'monthly' => $this->reports->leavesPerMonth(),
            'perCourse' => $this->reports->leavesPerCourse(),
            'topPermissionUsers' => $this->reports->topPermissionUsers(),
            'onLeaveToday' => $this->reports->onLeaveToday(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'monthly' => $this->reports->leavesPerMonth(),
            'perCourse' => $this->reports->leavesPerCourse(),
            'topPermissionUsers' => $this->reports->topPermissionUsers(),
            'onLeaveToday' => $this->reports->onLeaveToday(),
        ]);
    }
}
