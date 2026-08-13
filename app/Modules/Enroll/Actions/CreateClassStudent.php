<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudyClass;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use stdClass;

class CreateClassStudent
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(StudyClass $studyClass, array $data): stdClass
    {
        return DB::transaction(function () use ($studyClass, $data): stdClass {
            $class = $this->registrations->lockStudyClass($studyClass->id);
            $this->registrations->ensureClassHasSeat($class, 'name');

            $student = $this->registrations->createStudent($data, auth()->id());

            return $this->registrations->createEnrollment([
                'study_class_id' => $class->id,
                'student_id' => $student->id,
                'fee_amount' => $class->price,
                'document_fee_amount' => $class->document_price,
            ]);
        });
    }
}
