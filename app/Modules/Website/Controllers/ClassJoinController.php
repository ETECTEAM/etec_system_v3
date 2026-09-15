<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudyClass;
use App\Modules\Enroll\Queries\GetClassList;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Website\Requests\ClassJoinRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class ClassJoinController extends Controller
{
    public function create(StudyClass $studyClass, GetClassList $classList): Response|RedirectResponse
    {
        $studyClass->load([
            'course:id,title',
            'lesson:id,course_id,title',
            'teacher:id,name',
            'room:id,floor_id,room_number',
            'room.floor:id,building_id,name,level',
            'room.floor.building:id,name',
        ])->loadCount([
            'enrollments as current_students' => fn ($query) => $query->where('enrollment_status', 'active'),
        ]);

        if ($this->isLockedClassStatus($studyClass->status)) {
            return redirect()
                ->route('frontend.student-register.create')
                ->with('error', 'This class is no longer accepting join requests.');
        }

        return Inertia::render('frontend/class-join/JoinClass', [
            'classData' => $classList->presentClass($studyClass),
            'isLocked' => $this->isLockedForBrowser($studyClass->id),
        ]);
    }

    public function store(
        ClassJoinRequest $request,
        StudyClass $studyClass,
        StudentRegistrationService $registrations
    ): RedirectResponse {
        if ($this->isLockedClassStatus($studyClass->status)) {
            return back()->with('error', 'This class is no longer accepting join requests.');
        }

        if ($this->isLockedForBrowser($studyClass->id)) {
            return back()->with('error', 'You have already requested this class from this device.');
        }

        $student = DB::transaction(function () use ($request, $studyClass, $registrations): stdClass {
            $student = $registrations->findOrCreatePublicStudent($request->validated());
            $registrations->ensureStudentHasNoPendingOrActiveEnrollment($studyClass->id, $student->id);

            $registrations->createPendingEnrollment([
                'study_class_id' => $studyClass->id,
                'student_id' => $student->id,
                'source' => 'qr_code',
                'fee_amount' => $studyClass->price,
                'document_fee_amount' => $studyClass->document_price,
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
            ]);

            return $student;
        });

        $this->rememberBrowserJoin($studyClass->id);
        session()->put('pending_class_join', [
            'student_id' => $student->id,
            'study_class_id' => $studyClass->id,
        ]);

        return redirect()
            ->route('frontend.class-join.create', $studyClass->slug)
            ->with('success', 'Your request was sent. Save these Student Portal credentials while you wait for instructor approval.')
            ->with('student_portal_email', $student->email)
            ->with('attendance_code', $student->attendance_code);
    }

    public function approvalStatus(Request $request, StudyClass $studyClass): JsonResponse
    {
        $pending = $request->session()->get('pending_class_join');
        $approved = is_array($pending)
            && (int) ($pending['study_class_id'] ?? 0) === (int) $studyClass->id
            && DB::table('student_enrollments')
                ->where('study_class_id', $studyClass->id)
                ->where('student_id', (int) $pending['student_id'])
                ->where('enrollment_status', 'active')
                ->exists();

        if (! $approved) {
            return response()->json(['approved' => false]);
        }

        $student = DB::table('students')
            ->select('id', 'recovery_email')
            ->find((int) $pending['student_id']);

        abort_unless($student, 404);

        $request->session()->regenerate();
        $request->session()->put('student_portal_id', $student->id);
        $request->session()->forget('pending_class_join');

        if (! $student->recovery_email) {
            $request->session()->put('student_portal_recovery_setup_id', $student->id);
            $request->session()->put('student_portal_recovery_return_to', '/student-portal');

            return response()->json([
                'approved' => true,
                'portal_url' => route('frontend.student-portal.recovery-email.create'),
            ]);
        }

        return response()->json([
            'approved' => true,
            'portal_url' => route('frontend.student-portal.dashboard'),
        ]);
    }

    private function isLockedForBrowser(int $studyClassId): bool
    {
        return in_array($studyClassId, session()->get('qr_joined_class_ids', []), true);
    }

    private function rememberBrowserJoin(int $studyClassId): void
    {
        $joinedClassIds = session()->get('qr_joined_class_ids', []);
        $joinedClassIds[] = $studyClassId;

        session()->put('qr_joined_class_ids', array_values(array_unique($joinedClassIds)));
    }

    private function isLockedClassStatus(?string $status): bool
    {
        $normalizedStatus = match (strtolower((string) $status)) {
            'inactive' => 'pre_end',
            'completed' => 'ended',
            default => strtolower((string) $status),
        };

        return in_array($normalizedStatus, ['pre_end', 'ended', 'cancelled'], true);
    }
}
