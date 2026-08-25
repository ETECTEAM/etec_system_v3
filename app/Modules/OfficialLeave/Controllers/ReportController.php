<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfficialLeave;
use App\Modules\OfficialLeave\Services\ReportService;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize('viewReports', OfficialLeave::class);

        return Inertia::render('backend/official-leaves/Reports');
    }

    public function data(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewReports', OfficialLeave::class);

        return response()->json([
            'leavesPerMonth' => $this->reportService->getLeavesPerMonth(),
            'topStudents' => $this->reportService->getTopStudents(),
            'currentlyOnLeave' => $this->reportService->getCurrentlyOnLeave(),
            'classBreakdown' => $this->reportService->getClassBreakdown(),
        ]);
    }
}
