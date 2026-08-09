<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
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
