<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InstructorAttendanceBlock;
use App\Modules\Attendance\Actions\ApproveInstructorAttendanceBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class InstructorAttendanceBlockController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('backend/instructor-attendance-blocks/Index', [
            'filters' => $request->only(['status']),
            'blocks' => $this->paginate($request),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->paginate($request));
    }

    private function paginate(Request $request): LengthAwarePaginator
    {
        return InstructorAttendanceBlock::query()
            ->with([
                // Phone lives on instructor_data, not users - the instructor's own
                // profile record (see App\Models\User::instructorData()).
                'instructor:id,name',
                'instructor.instructorData:user_id,phone',
                'triggeredBySession:id,study_class_id,session_date',
                'triggeredBySession.studyClass:id,course_id',
                'triggeredBySession.studyClass.course:id,title',
                'reviewer:id,name',
            ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('blocked_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function approve(InstructorAttendanceBlock $block, Request $request, ApproveInstructorAttendanceBlock $action): RedirectResponse
    {
        $action->handle($block, $request->user());

        return back()->with('success', 'Instructor unblocked. Attendance tracking restored on all classes.');
    }
}
