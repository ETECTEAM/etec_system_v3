<?php

namespace App\Modules\Enroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Modules\Enroll\Actions\SetAllCourseStartDates;
use App\Modules\Enroll\Actions\SetCourseEnrollConfig;
use App\Modules\Enroll\Queries\GetCourseEnrollConfigs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseEnrollConfigController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('backend/students/EnrollConfig');
    }

    public function data(Request $request, GetCourseEnrollConfigs $configs): JsonResponse
    {
        return response()->json($configs->handle($request));
    }

    public function update(Request $request, Course $course, SetCourseEnrollConfig $setConfig): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,closed'],
            'start_date' => ['nullable', 'date'],
        ]);

        $config = $setConfig->handle($course, $validated);

        return response()->json([
            'status' => $config->status,
            'start_date' => optional($config->start_date)->format('Y-m-d'),
        ]);
    }

    public function bulkUpdateStartDate(Request $request, SetAllCourseStartDates $setAllStartDates): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
        ]);

        $updated = $setAllStartDates->handle($validated['start_date'] ?? null);

        return response()->json(['updated' => $updated]);
    }
}
