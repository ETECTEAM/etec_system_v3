<?php

namespace App\Modules\Enroll\Queries;

use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Modules\Enroll\Queries\GetClassList;
use Illuminate\Database\Eloquent\Builder;

class GetClassDetails
{
    public function __construct(
        private readonly GetClassList $classList
    ) {}

    public function handle(StudyClass $studyClass): array
    {
        $studyClass->load([
            'course:id,title,price',
            'lesson:id,course_id,title',
            'teacher:id,name',
            'room:id,floor_id,room_number',
            'room.floor:id,building_id,name,level',
            'room.floor.building:id,name',
        ])->loadCount([
            'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
        ]);

        $enrollments = StudentEnrollment::query()
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active')
            ->with(['student:id,name', 'student.student:id,user_id,gender,phone'])
            ->orderBy('id')
            ->get();

        return [
            'classData' => $this->classList->presentClass($studyClass),
            'students' => $enrollments
                ->map(fn (StudentEnrollment $enrollment, int $index) => $this->presentEnrollment($enrollment, $index + 1))
                ->values(),
            'depositSummary' => $this->summary($studyClass),
        ];
    }

    private function presentEnrollment(StudentEnrollment $enrollment, int $rosterNo): array
    {
        $feeAmount = (float) $enrollment->fee_amount;
        $amountPaid = (float) $enrollment->amount_paid;

        return [
            'id' => $enrollment->student_id,
            'roster_no' => $rosterNo,
            'enrollment_id' => $enrollment->id,
            'name' => $enrollment->student?->name ?? '-',
            'gender' => $enrollment->student?->student?->gender ?? '-',
            'phone' => $enrollment->student?->student?->phone,
            'fee_amount' => $feeAmount,
            'amount_paid' => $amountPaid,
            'deposit_amount' => $amountPaid,
            'payment_date' => $enrollment->paid_at?->format('Y-m-d'),
            'payment_status' => ucfirst($enrollment->payment_status),
            'remaining_balance' => max($feeAmount - $amountPaid, 0),
        ];
    }

    private function summary(StudyClass $studyClass): array
    {
        $active = StudentEnrollment::query()
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active');

        return [
            'total_students' => (clone $active)->count(),
            'paid_students' => (clone $active)->where('payment_status', 'paid')->count(),
            'partial_students' => (clone $active)->where('payment_status', 'partial')->count(),
            'unpaid_students' => (clone $active)->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'total_deposit_collected' => (float) (clone $active)->sum('amount_paid'),
        ];
    }
}
