<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudyClass;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use stdClass;

class EnrollStudent
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(StudyClass $studyClass, int $studentId): stdClass
    {
        return DB::transaction(function () use ($studyClass, $studentId): stdClass {
            $class = $this->registrations->lockStudyClass($studyClass->id);
            $this->registrations->ensureStudentIsNotEnrolledInClass($class->id, $studentId);
            $this->registrations->ensureClassHasSeat($class);

            return $this->registrations->createEnrollment([
                'study_class_id' => $class->id,
                'student_id' => $studentId,
                'fee_amount' => $class->price,
                'document_fee_amount' => $class->document_price,
            ]);
        });
    }
}
