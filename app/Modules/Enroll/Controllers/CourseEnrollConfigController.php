<?php

namespace App\Modules\Enroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
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

    // Add a new schedule (one time slot) to a course. time_id = null creates the
    // course's default/general schedule - allowed only when none exists yet.
    public function store(Request $request, Course $course, SetCourseEnrollConfig $setConfig): JsonResponse
    {
        $validated = $request->validate([
            'time_id' => ['nullable', 'integer', 'exists:times,id'],
            'status' => ['required', 'string', 'in:open,closed'],
            'start_date' => ['nullable', 'date'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'course_price' => ['nullable', 'numeric', 'min:0'],
            'selected_price_type' => ['required', 'string', 'in:unit,course'],
            'document_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->ensureNoDuplicateSchedule($course->id, $validated['time_id'] ?? null);

        $config = $setConfig->create($course, $validated['time_id'] ?? null, $validated);

        return response()->json($this->presentConfig($config), 201);
    }

    public function update(Request $request, CourseEnrollConfig $config, SetCourseEnrollConfig $setConfig): JsonResponse
    {
        $validated = $request->validate([
            'time_id' => ['nullable', 'integer', 'exists:times,id'],
            'status' => ['required', 'string', 'in:open,closed'],
            'start_date' => ['nullable', 'date'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'course_price' => ['nullable', 'numeric', 'min:0'],
            'selected_price_type' => ['required', 'string', 'in:unit,course'],
            'document_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->ensureNoDuplicateSchedule($config->course_id, $validated['time_id'] ?? null, $config->id);

        $updated = $setConfig->update($config, $validated);

        return response()->json($this->presentConfig($updated));
    }

    public function destroy(CourseEnrollConfig $config): JsonResponse
    {
        $config->delete();

        return response()->json(['deleted' => true]);
    }

    public function bulkUpdateStartDate(Request $request, SetAllCourseStartDates $setAllStartDates): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
        ]);

        $updated = $setAllStartDates->handle($validated['start_date'] ?? null);

        return response()->json(['updated' => $updated]);
    }

    // The course's display position on the public student-register list -
    // lower numbers show first (Basic IT = 1, Office Word Excel = 2, ...).
    // Lives on the course (not on each schedule row) because ordering is a
    // per-course concern; clearing the value drops it back to title sort.
    public function updateCourseOrder(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'enroll_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $course->update(['enroll_order' => $validated['enroll_order'] ?? null]);

        return response()->json([
            'id' => $course->id,
            'enroll_order' => $course->enroll_order,
        ]);
    }

    private function ensureNoDuplicateSchedule(int $courseId, ?int $timeId, ?int $exceptId = null): void
    {
        $query = CourseEnrollConfig::query()
            ->where('course_id', $courseId)
            ->where('time_id', $timeId);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        abort_if($query->exists(), 422, 'This course already has a schedule for that time slot.');
    }

    private function presentConfig(CourseEnrollConfig $config): array
    {
        return [
            'id' => $config->id,
            'time_id' => $config->time_id,
            'time_name' => $config->time?->time_name,
            'enroll_status' => $config->status,
            'start_date' => optional($config->start_date)->format('Y-m-d'),
            'unit_price' => (float) $config->unit_price,
            'course_price' => (float) $config->course_price,
            'selected_price_type' => $config->selected_price_type,
            'resolved_price' => $config->resolvedPrice(),
            'document_price' => (float) $config->document_price,
        ];
    }
}
