<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollStudent
{
    public function handle(StudyClass $studyClass, int $studentId): StudentEnrollment
    {
        return DB::transaction(function () use ($studyClass, $studentId): StudentEnrollment {
            $studyClass = StudyClass::query()->lockForUpdate()->findOrFail($studyClass->id);

            $alreadyEnrolled = StudentEnrollment::query()
                ->where('study_class_id', $studyClass->id)
                ->where('student_id', $studentId)
                ->exists();

            if ($alreadyEnrolled) {
                throw ValidationException::withMessages([
                    'student_id' => 'This student is already enrolled in this class.',
                ]);
            }

            $activeCount = StudentEnrollment::query()
                ->where('study_class_id', $studyClass->id)
                ->where('enrollment_status', 'active')
                ->count();

            if ($activeCount >= $studyClass->capacity) {
                throw ValidationException::withMessages([
                    'student_id' => 'This class is full.',
                ]);
            }

            return StudentEnrollment::create([
                'study_class_id' => $studyClass->id,
                'student_id' => $studentId,
                'enrollment_status' => 'active',
                'payment_status' => 'unpaid',
                'fee_amount' => $studyClass->price,
                'amount_paid' => 0,
                'enrolled_at' => now(),
            ]);
        });
    }
}
