<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudyClass;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use stdClass;

class EnrollStudent
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    // $overrides lets callers that are re-creating an enrollment on behalf of
    // an existing one (see MoveStudentEnrollment) carry fields like source,
    // amount_paid, paid_at and enrolled_at forward instead of losing them to
    // createEnrollment()'s fresh-registration defaults. study_class_id and
    // student_id are always the ones this method resolves, never the caller's.
    public function handle(StudyClass $studyClass, int $studentId, bool $force = false, array $overrides = []): stdClass
    {
        return DB::transaction(function () use ($studyClass, $studentId, $force, $overrides): stdClass {
            $class = $this->registrations->lockStudyClass($studyClass->id);
            $this->registrations->ensureStudentIsNotEnrolledInClass($class->id, $studentId);

            if ($force) {
                $this->registrations->expandCapacityToFit($class);
            } else {
                $this->registrations->ensureClassHasSeat($class);
            }

            return $this->registrations->createEnrollment(array_merge($overrides, [
                'study_class_id' => $class->id,
                'student_id' => $studentId,
                'fee_amount' => $class->price,
                'document_fee_amount' => $class->document_price,
            ]));
        });
    }
}
