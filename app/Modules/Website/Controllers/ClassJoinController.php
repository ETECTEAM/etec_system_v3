<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudyClass;
use App\Modules\Enroll\Queries\GetClassList;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Website\Requests\ClassJoinRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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

        DB::transaction(function () use ($request, $studyClass, $registrations): void {
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
        });

        $this->rememberBrowserJoin($studyClass->id);

        return redirect()
            ->route('frontend.class-join.create', $studyClass)
            ->with('success', 'Your request was sent. An instructor will review it before approval.');
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
