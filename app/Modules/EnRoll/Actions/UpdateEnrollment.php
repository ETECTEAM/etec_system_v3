<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentPayment;
use Illuminate\Support\Facades\DB;

class UpdateEnrollment
{
    /**
     * Mutate the editable parts of an enrollment: roster status, amount paid
     * (reconciled against the fee + document fee totals), payment status and
     * payment date. Keeps the aggregate fields in sync, and when the amount
     * paid has increased by a positive delta writes a payment-history row so
     * the ledger never silently overwrites an earlier payment.
     */
    public function handle(StudentEnrollment $enrollment, array $data): StudentEnrollment
    {
        return DB::transaction(function () use ($enrollment, $data): StudentEnrollment {
            $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);

            $feeAmount = (float) $enrollment->fee_amount;
            $documentFeeAmount = (float) $enrollment->document_fee_amount;
            $totalDue = $feeAmount + $documentFeeAmount;
            $amountPaid = (float) ($data['amount_paid'] ?? $enrollment->amount_paidende);
            $amountPaid = min($amountPaid, $totalDue);

            $enrollment->forceFill([
                'enrollment_status' => $data['enrollment_status'] ?? $enrollment->enrollment_status,
                'amount_paid' => $amountPaid,
                'payment_status' => $this->paymentStatus($amountPaid, $totalDue),
                'paid_at' => ! empty($data['payment_date']) ? $data['payment_date'] : $enrollment->paid_at,
            ])->save();

            $previousPaid = (float) $enrollment->getOriginal('amount_paid');
            $delta = $amountPaid - $previousPaid;

            if ($delta > 0) {
                StudentEnrollmentPayment::create([
                    'student_enrollment_id' => $enrollment->id,
                    'study_class_id' => $enrollment->study_class_id,
                    'student_id' => $enrollment->student_id,
                    'amount' => $delta,
                    'payment_method' => 'cash',
                    'payment_date' => ! empty($data['payment_date']) ? $data['payment_date'] : now(),
                    'payment_status' => 'completed',
                    'recorded_by' => auth()->id(),
                ]);
            }

            return $enrollment;
        });
    }

    private function paymentStatus(float $amountPaid, float $totalDue): string
    {
        if ($amountPaid <= 0) {
            return 'unpaid';
        }

        return $amountPaid < $totalDue ? 'partial' : 'paid';
    }
}
