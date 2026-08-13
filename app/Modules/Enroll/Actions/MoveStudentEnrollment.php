<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class MoveStudentEnrollment
{
    public function __construct(private readonly EnrollStudent $enrollStudent) {}

    public function handle(StudentEnrollment $enrollment, StudyClass $targetClass, bool $force = false): stdClass
    {
        return DB::transaction(function () use ($enrollment, $targetClass, $force): stdClass {
            if ($enrollment->study_class_id === $targetClass->id) {
                throw ValidationException::withMessages([
                    'study_class_id' => 'This student is already in that class.',
                ]);
            }

            $sourceClassId = $enrollment->study_class_id;
            $amountPaid = (float) $enrollment->amount_paid;
            $totalDue = (float) $targetClass->price + (float) $targetClass->document_price;

            $moved = $this->enrollStudent->handle($targetClass, $enrollment->student_id, $force, [
                'source' => $enrollment->source,
                'amount_paid' => $amountPaid,
                'payment_status' => $this->paymentStatus($amountPaid, $totalDue),
                'paid_at' => $enrollment->paid_at,
                'enrolled_at' => $enrollment->enrolled_at,
            ]);

            $enrollment->update(['enrollment_status' => 'cancelled']);

            $this->cancelSourceClassIfNowEmpty($sourceClassId);

            return $moved;
        });
    }

    // A move is the only action that can leave a class with zero active
    // students as a direct side effect, so this is the one place that checks
    // for it. Marks the class cancelled (hidden from the class list) rather
    // than deleting it outright - study_class_id has cascadeOnDelete on
    // enrollments/attendances, so a hard delete here would also destroy the
    // cancelled-enrollment record this same move just created.
    private function cancelSourceClassIfNowEmpty(int $studyClassId): void
    {
        $stillHasActiveStudents = StudentEnrollment::query()
            ->where('study_class_id', $studyClassId)
            ->where('enrollment_status', 'active')
            ->exists();

        if (! $stillHasActiveStudents) {
            StudyClass::whereKey($studyClassId)->update(['status' => 'cancelled']);
        }
    }

    // Mirrors RecordEnrollmentDeposit's tiering - the target class's price can
    // differ from the source class's, so payment_status is recomputed against
    // the carried-over amount_paid rather than copied as-is.
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
