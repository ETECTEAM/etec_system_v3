<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Modules\Instructor\Services\InstructorAvailabilityAdminService;
use App\Modules\Instructor\Services\InstructorAvailabilityOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-facing "busy time" overview: a weekly grid showing, for every active
 * instructor, the time ranges they are already teaching, have manually
 * blocked, are available for, or are off each day - plus the controls for an
 * admin / super_admin to block/unblock a slot, open/close a non-working slot,
 * and toggle an instructor's available-for-class master switch.
 * Admin/super_admin only (enforced on the route group).
 */
class InstructorAvailabilityController extends Controller
{
    public function __construct(private InstructorAvailabilityAdminService $admin) {}

    public function index(Request $request, InstructorAvailabilityOverviewService $service): Response
    {
        return Inertia::render('backend/instructor-availability/Index', $service->overview());
    }

    /** Same payload as index(), as JSON - the grid re-fetches this after every edit. */
    public function data(Request $request, InstructorAvailabilityOverviewService $service): JsonResponse
    {
        return response()->json($service->overview());
    }

    public function blockSlot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'instructor_id' => ['required', 'integer', 'exists:instructor_data,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'time_id' => ['required', 'integer', 'exists:times,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $instructor = InstructorData::query()->findOrFail($data['instructor_id']);

        $block = $this->admin->blockSlot(
            $instructor,
            $data['day_of_week'],
            $data['time_id'],
            $data['reason'] ?? null,
            $request->user()->id,
        );

        return response()->json(['id' => $block->id], 201);
    }

    public function unblockSlot(InstructorScheduleBlock $block): JsonResponse
    {
        $this->admin->unblockSlot($block);

        return response()->json(['deleted' => true]);
    }

    public function openSlot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'instructor_id' => ['required', 'integer', 'exists:instructor_data,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'time_id' => ['required', 'integer', 'exists:times,id'],
        ]);

        $instructor = InstructorData::query()->findOrFail($data['instructor_id']);

        $availability = $this->admin->openSlot($instructor, $data['day_of_week'], $data['time_id']);

        return response()->json(['id' => $availability->id], 201);
    }

    public function closeSlot(InstructorAvailability $availability): JsonResponse
    {
        $this->admin->closeSlot($availability);

        return response()->json(['deleted' => true]);
    }

    public function toggleAvailableForClass(Request $request, InstructorData $instructor): JsonResponse
    {
        $data = $request->validate([
            'available_for_class' => ['required', 'boolean'],
        ]);

        $instructor->update(['available_for_class' => $data['available_for_class']]);

        return response()->json(['available_for_class' => (bool) $instructor->available_for_class]);
    }
}
