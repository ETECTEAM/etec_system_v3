<?php

namespace App\Modules\Enroll\Queries;

use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentPayment;
use App\Models\StudyClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class GetClassDetails
{
    public function __construct(
        private readonly GetClassList $classList
    ) {}

    public function handle(StudyClass $studyClass): array
    {
        $studyClass->load([
            'course:id,title',
            'lesson:id,course_id,title',
            'teacher:id,name',
            'room:id,floor_id,room_number',
            'room.floor:id,building_id,name,level',
            'room.floor.building:id,name',
        ])->loadCount([
            'enrollments as current_students' => fn (Builder $query) =>
                $query->where('enrollment_status', 'active'),
        ]);

        /*
         * Active students in this class. The payments ledger
         * (student_enrollment_payments) may not be migrated yet, so only
         * eager-load it when the table actually exists — otherwise the query
         * below crashes on a missing table. No fake payment rows are ever
         * generated: payment_history is [] until the table is present.
         */
        $hasPaymentsTable = Schema::hasTable('student_enrollment_payments');

        $enrollments = StudentEnrollment::query()
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active')
            ->with(['student:id,full_name,gender,phone'])
            ->when($hasPaymentsTable, fn (Builder $query) => $query->with([
                'payments:id,student_enrollment_id,amount,payment_method,payment_date,payment_status,reference_number,remarks,recorded_by',
                'payments.recordedBy:id,name',
            ]));

        $enrollments = $enrollments->orderBy('id')->get();

        return [
            'classData' => $this->classList->presentClass($studyClass),

            'students' => $enrollments
                ->map(
                    fn (StudentEnrollment $enrollment, int $index) =>
                        $this->presentEnrollment($enrollment, $index + 1, $studyClass, $hasPaymentsTable)
                )
                ->values(),

            'depositSummary' => $this->summary($studyClass),
        ];
    }

    private function presentEnrollment(
        StudentEnrollment $enrollment,
        int $rosterNo,
        StudyClass $studyClass,
        bool $hasPaymentsTable
    ): array {
        $feeAmount = (float) $enrollment->fee_amount;

        $documentFeeAmount = (float) $enrollment->document_fee_amount;

        $amountPaid = (float) $enrollment->amount_paid;

        $totalDue = $feeAmount + $documentFeeAmount;

        // Marked paid means nothing is owed, even when no amount was recorded (paid at the desk).
        $remainingBalance = $enrollment->payment_status === 'paid'
            ? 0
            : max($totalDue - $amountPaid, 0);

        return [
            'id' => $enrollment->student_id,

            'roster_no' => $rosterNo,

            'enrollment_id' => $enrollment->id,

            'public_token' => $enrollment->public_token,

            'name' => $enrollment->student?->full_name ?? '-',

            'gender' => $enrollment->student?->gender ?? '-',

            'phone' => $enrollment->student?->phone ?? '-',

            'fee_amount' => $feeAmount,

            'document_fee_amount' => $documentFeeAmount,

            'amount_paid' => $amountPaid,

            'deposit_amount' => $amountPaid,

            'payment_date' => $enrollment->paid_at?->format('Y-m-d'),

            'payment_status' => ucfirst(
                (string) $enrollment->payment_status
            ),

            'remaining_balance' => $remainingBalance,

            // Information shown in the Student Deposit Details modal.
            'enrollment_status' => ucfirst(
                (string) $enrollment->enrollment_status
            ),

            'enrolled_at' => optional($enrollment->enrolled_at)->format('Y-m-d'),

            'class_title' => $studyClass->title,

            'course' => $studyClass->course?->title ?? '-',

            /*
             * Real per-payment history from the payments ledger when its table
             * exists; an empty array otherwise. Never fabricated.
             */
            'payment_history' => $hasPaymentsTable
                ? $enrollment->payments
                    ->sortBy('payment_date')
                    ->map(fn (StudentEnrollmentPayment $payment) => [
                        'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                        'amount' => (float) $payment->amount,
                        'payment_method' => ucfirst((string) ($payment->payment_method ?? '-')),
                        'reference_number' => $payment->reference_number ?? '-',
                        'payment_status' => ucfirst((string) $payment->payment_status),
                        'recorded_by' => $payment->recordedBy?->name ?? '-',
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }

    private function summary(StudyClass $studyClass): array
    {
        $active = StudentEnrollment::query()
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active');

        return [
            'total_students' => (clone $active)->count(),

            'paid_students' => (clone $active)
                ->where('payment_status', 'paid')
                ->count(),

            'partial_students' => (clone $active)
                ->where('payment_status', 'partial')
                ->count(),

            'unpaid_students' => (clone $active)
                ->where('payment_status', 'unpaid')
                ->count(),

            'students_with_balance' => (clone $active)
                ->whereIn('payment_status', ['partial', 'unpaid'])
                ->count(),

            'total_deposit_collected' => (float) (clone $active)
                ->sum('amount_paid'),
        ];
    }
}