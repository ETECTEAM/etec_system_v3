<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordEnrollmentDeposit
{
    public function handle(StudentEnrollment $enrollment, float $depositAmount): StudentEnrollment
    {
        return DB::transaction(function () use ($enrollment, $depositAmount): StudentEnrollment {
            $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);

            $newAmountPaid = (float) $enrollment->amount_paid + $depositAmount;
            $totalDue = (float) $enrollment->fee_amount + (float) $enrollment->document_fee_amount;

            if ($newAmountPaid > $totalDue) {
                throw ValidationException::withMessages([
                    'deposit_amount' => 'Deposit amount cannot make paid amount greater than the fee amount.',
                ]);
            }

            $enrollment->forceFill([
                'amount_paid' => $newAmountPaid,
                'payment_status' => $this->paymentStatus($newAmountPaid, $totalDue),
                'paid_at' => now(),
            ])->save();

            StudentEnrollmentPayment::create([
                'student_enrollment_id' => $enrollment->id,
                'study_class_id' => $enrollment->study_class_id,
                'student_id' => $enrollment->student_id,
                'amount' => $depositAmount,
                'payment_method' => 'cash',
                'payment_date' => now(),
                'payment_status' => 'completed',
                'recorded_by' => auth()->id(),
            ]);

            return $enrollment;
        });
    }

    private function paymentStatus(float $amountPaid, float $totalDue): string
    {
        if ($amountPaid <= 0) {
            return 'unpaid';
        }

        if ($amountPaid < $totalDue) {
            return 'partial';
        }

        return 'paid';
    }
}
