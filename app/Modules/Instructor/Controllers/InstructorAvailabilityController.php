<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Instructor\Services\InstructorAvailabilityOverviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-facing "busy time" overview: a weekly grid showing, for every active
 * instructor, the time ranges they are already teaching or have manually
 * blocked each day. Admin/super_admin only — instructors manage their own
 * availability from the instructor dashboard instead.
 */
class InstructorAvailabilityController extends Controller
{
    public function index(Request $request, InstructorAvailabilityOverviewService $service): Response
    {
        $overview = $service->overview();

        return Inertia::render('backend/instructor-availability/Index', [
            'slots' => $overview['slots'],
            'instructors' => $overview['instructors'],
        ]);
    }
}
