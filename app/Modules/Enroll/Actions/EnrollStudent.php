<?php

namespace App\Modules\Enroll\Actions;

use App\Models\CourseEnrollConfig;
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

            $config = $this->resolveEnrollConfig($class);
            $resolvedFee = $config?->resolvedPrice() ?? (float) $class->price;
            $resolvedDocFee = $config !== null ? (float) $config->document_price : (float) $class->document_price;
            $unitPrice = $config && (float) $config->unit_price > 0 ? (float) $config->unit_price : $resolvedFee;

            return $this->registrations->createEnrollment(array_merge($overrides, [
                'study_class_id' => $class->id,
                'student_id' => $studentId,
                'fee_amount' => $resolvedFee,
                'unit_price' => $unitPrice,
                'document_fee_amount' => $resolvedDocFee,
            ]));
        });
    }

    private function resolveEnrollConfig(stdClass $class): ?CourseEnrollConfig
    {
        return CourseEnrollConfig::forCourseTime($class->course_id, $class->time_id);
    }
}
