<?php

namespace App\Modules\Enroll\Queries;

use App\Models\StudentEnrollment;
use App\Support\InstructorDisplayName;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Lists students who self-registered via the public /classes page
 * (StudentEnrollment.source = 'public_website'), shaped for the dashboard's
 * "Registrations" tab and its receipt-printing action.
 */
class GetPublicRegistrations
{
    public function handle(Request $request): LengthAwarePaginator
    {
        $search = trim($request->string('search')->toString());

        return $this->baseQuery($search)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (StudentEnrollment $enrollment) => $this->present($enrollment));
    }

    public function pendingCount(?string $search = null): int
    {
        return $this->baseQuery(trim((string) $search))
            ->where(function (Builder $query): void {
                $query->where('enrollment_status', 'pending')
                    ->orWhere('no_room_and_instructor', true)
                    ->orWhere('no_instructor', true)
                    ->orWhere('no_room', true);
            })
            ->count();
    }

    private function baseQuery(string $search = ''): Builder
    {
        return StudentEnrollment::query()
            ->whereIn('source', ['public_website', 'qr_code'])
            ->whereIn('enrollment_status', ['active', 'pending', 'unassigned'])
            ->with([
                'student:id,full_name,gender,phone',
                'studyClass:id,title,course_id,term_id,time_id,teacher_id,room_id',
                'studyClass.course:id,title',
                'studyClass.course.enrollConfigs:id,course_id,time_id,start_date',
                'studyClass.term:id,term_name',
                'studyClass.time:id,time_name',
                'studyClass.teacher:id,name',
                'studyClass.room:id,floor_id,room_number',
                'studyClass.room.floor:id,building_id,name,level',
                'studyClass.room.floor.building:id,name',
                // For a classless (unassigned) row - the course/term/time the
                // student actually asked for, snapshotted at registration time.
                'course:id,title',
                'term:id,term_name',
                'time:id,time_name',
            ])
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->latest('id');
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->whereHas('student', function (Builder $query) use ($search): void {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
                ->orWhereHas('studyClass', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('studyClass.course', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('course', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"));
        });
    }

    private function present(StudentEnrollment $enrollment): array
    {
        return [
            'enrollment_id' => $enrollment->id,
            'public_token' => $enrollment->public_token,
            'name' => $enrollment->student?->full_name ?? '-',
            'gender' => $enrollment->student?->gender ?? '-',
            'phone' => $enrollment->student?->phone ?? '-',
            'class_id' => $enrollment->study_class_id,
            'class_title' => $enrollment->studyClass?->title ?? '-',
            'course_title' => $enrollment->studyClass?->course?->title ?? $enrollment->course?->title,
            // The class's term name verbatim ("Mon & Thu"), shown as-is rather
            // than the weekday range parseTermDays() expands it into - the
            // receipt-only terms ("Mon & Thu", "Sat & Sun") otherwise print a
            // whole span like "Mon & Tue & Wed & Thu" until an instructor
            // narrows the term.
            'term_name' => $enrollment->studyClass?->term?->term_name,
            // Only meaningful while there's no class yet - what the student
            // asked for at registration time (see the course_title fallback
            // above). A class's real schedule is shown via study_days/
            // start_time/end_time below once one exists.
            'requested_term' => $enrollment->study_class_id === null ? $enrollment->term?->term_name : null,
            'requested_time' => $enrollment->study_class_id === null ? $enrollment->time?->time_name : null,
            // The class start date staff set per course on the Enroll Config
            // page (course_enroll_configs.start_date) - this is what the
            // receipt's "កាលបរិច្ឆេទចូលរៀន" field prints, not the payment date.
            // Time-slot-specific config when the class has one, else the default.
            'enroll_start_date' => optional(
                $enrollment->studyClass?->course?->enrollConfigForTime($enrollment->studyClass?->time_id)
            )?->start_date?->format('Y-m-d'),
            'teacher_name' => InstructorDisplayName::format($enrollment->studyClass?->teacher?->name, ''),
            'building' => $enrollment->studyClass?->room?->floor?->building?->name,
            'floor' => $enrollment->studyClass?->room?->floor?->name,
            'room' => $enrollment->studyClass?->room?->room_number ?? ($enrollment->studyClass?->isOnline() ? 'Online' : null),
            'study_days' => $enrollment->studyClass?->scheduleStudyDays() ?? [],
            'start_time' => $this->formatTime($enrollment->studyClass?->scheduleStartTime()),
            'end_time' => $this->formatTime($enrollment->studyClass?->scheduleEndTime()),
            'fee_amount' => (float) $enrollment->fee_amount,
            'unit_price' => $enrollment->unit_price !== null ? (float) $enrollment->unit_price : null,
            'document_fee_amount' => (float) $enrollment->document_fee_amount,
            'amount_paid' => (float) $enrollment->amount_paid,
            'payment_status' => ucfirst($enrollment->payment_status),
            'enrollment_status' => ucfirst($enrollment->enrollment_status),
            'source' => $enrollment->source,
            'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d h:i A'),
            // RegisterStudentForSchedule couldn't slot this into a class (no
            // room/instructor free at the time) - the Registrations tab shows
            // an "Assign to Class" action instead of the usual class/payment
            // columns whenever any of these is true.
            'needs_manual_scheduling' => $enrollment->no_room_and_instructor || $enrollment->no_instructor || $enrollment->no_room,
        ];
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
