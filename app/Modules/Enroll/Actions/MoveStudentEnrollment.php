<?php

namespace App\Modules\Enroll\Actions;

use App\Models\CourseEnrollConfig;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class MoveStudentEnrollment
{
    public function __construct(
        private readonly EnrollStudent $enrollStudent,
        private readonly StudentRegistrationService $registrations,
    ) {}

    public function handle(StudentEnrollment $enrollment, StudyClass $targetClass, bool $force = false): stdClass
    {
        return DB::transaction(function () use ($enrollment, $targetClass, $force): stdClass {
            if ($enrollment->study_class_id === $targetClass->id) {
                throw ValidationException::withMessages([
                    'study_class_id' => 'This student is already in that class.',
                ]);
            }

            // RegisterStudentForSchedule parked this registration with no
            // class at all (no room/instructor was free - see
            // no_room_and_instructor/no_instructor/no_room) - assign it into
            // the chosen class in place instead of cancelling a "move" that
            // never had a real source class.
            if ($enrollment->study_class_id === null) {
                return $this->assignUnassigned($enrollment, $targetClass, $force);
            }

            $sourceClassId = $enrollment->study_class_id;
            $amountPaid = (float) $enrollment->amount_paid;
            $resolved = $this->resolveClassPrice($targetClass);
            $totalDue = $resolved['price'] + $resolved['document_price'];

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

    private function assignUnassigned(StudentEnrollment $enrollment, StudyClass $targetClass, bool $force): stdClass
    {
        $class = $this->registrations->lockStudyClass($targetClass->id);
        $this->registrations->ensureStudentIsNotEnrolledInClass((int) $class->id, (int) $enrollment->student_id);

        if ($force) {
            $this->registrations->expandCapacityToFit($class);
        } else {
            $this->registrations->ensureClassHasSeat($class);
        }

        // The course-level price snapshotted while parked (see
        // RegisterStudentForSchedule::saveUnassignedEnrollment) can differ
        // from this specific class's price, so payment_status is recomputed
        // against the amount already paid rather than left as-is.
        $amountPaid = (float) $enrollment->amount_paid;
        $resolved = $this->resolveClassPrice($class);
        $totalDue = $resolved['price'] + $resolved['document_price'];

        $enrollment->update([
            'study_class_id' => $class->id,
            'enrollment_status' => 'active',
            'fee_amount' => $resolved['price'],
            'document_fee_amount' => $resolved['document_price'],
            'payment_status' => $this->paymentStatus($amountPaid, $totalDue),
            'no_room_and_instructor' => false,
            'no_instructor' => false,
            'no_room' => false,
        ]);

        return (object) $enrollment->fresh()->toArray();
    }

    private function resolveClassPrice(stdClass|StudyClass $class): array
    {
        $query = CourseEnrollConfig::where('course_id', $class->course_id);

        if ($class->time_id !== null) {
            $query->where('time_id', $class->time_id);
        } else {
            $query->whereNull('time_id');
        }

        $config = $query->first();

        return [
            'price' => $config?->resolvedPrice() ?? (float) $class->price,
            'document_price' => $config !== null ? (float) $config->document_price : (float) $class->document_price,
        ];
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
