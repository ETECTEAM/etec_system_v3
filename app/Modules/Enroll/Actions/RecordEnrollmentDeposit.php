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
            $feeAmount = (float) $enrollment->fee_amount;

            if ($newAmountPaid > $feeAmount) {
                throw ValidationException::withMessages([
                    'deposit_amount' => 'Deposit amount cannot make paid amount greater than the fee amount.',
                ]);
            }

            $enrollment->forceFill([
                'amount_paid' => $newAmountPaid,
                'payment_status' => $this->paymentStatus($newAmountPaid, $feeAmount),
                'paid_at' => now(),
            ])->save();

            return $enrollment;
        });
    }

    private function paymentStatus(float $amountPaid, float $feeAmount): string
    {
        if ($amountPaid <= 0) {
            return 'unpaid';
        }

        if ($amountPaid < $feeAmount) {
            return 'partial';
        }

        return 'paid';
    }
}
