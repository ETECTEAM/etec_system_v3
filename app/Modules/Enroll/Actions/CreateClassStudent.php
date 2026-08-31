<?php

namespace App\Modules\Enroll\Actions;

use App\Models\CourseEnrollConfig;
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

            $config = $this->resolveEnrollConfig($class);
            $resolvedFee = $config?->resolvedPrice() ?? (float) $class->price;
            $resolvedDocFee = $config !== null ? (float) $config->document_price : (float) $class->document_price;
            $unitPrice = $config && (float) $config->unit_price > 0 ? (float) $config->unit_price : $resolvedFee;

            return $this->registrations->createEnrollment([
                'study_class_id' => $class->id,
                'student_id' => $student->id,
                'fee_amount' => $resolvedFee,
                'unit_price' => $unitPrice,
                'document_fee_amount' => $resolvedDocFee,
            ]);
        });
    }

    private function resolveEnrollConfig(stdClass $class): ?CourseEnrollConfig
    {
        return CourseEnrollConfig::forCourseTime($class->course_id, $class->time_id);
    }
}
