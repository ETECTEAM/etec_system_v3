<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveStudentEnrollment
{
    public function __construct(private readonly EnrollStudent $enrollStudent) {}

    public function handle(StudentEnrollment $enrollment, StudyClass $targetClass, bool $force = false): StudentEnrollment
    {
        return DB::transaction(function () use ($enrollment, $targetClass, $force): StudentEnrollment {
            if ($enrollment->study_class_id === $targetClass->id) {
                throw ValidationException::withMessages([
                    'study_class_id' => 'This student is already in that class.',
                ]);
            }

            $moved = $this->enrollStudent->handle($targetClass, $enrollment->student_id, $force);

            $enrollment->update(['enrollment_status' => 'cancelled']);

            return $moved;
        });
    }
}
