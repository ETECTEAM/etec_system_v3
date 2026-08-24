<?php

namespace App\Modules\Enroll\Services;

use App\Models\StudyClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class StudentRegistrationService
{
    public function createStudent(array $data, ?int $creatorUserId = null, array $extra = []): stdClass
    {
        $now = now();
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $creatorUserId,
            'full_name' => $data['name'] ?? $data['full_name'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'student_status' => 'active',
            'course_id' => $extra['course_id'] ?? $data['course_id'] ?? null,
            'term_id' => $extra['term_id'] ?? $data['term_id'] ?? null,
            'time_id' => $extra['time_id'] ?? $data['time_id'] ?? null,
            'fee_amount' => isset($extra['fee_amount'])
                ? round((float) $extra['fee_amount'], 2)
                : (isset($data['price']) ? round((float) $data['price'], 2) : null),
            'document_fee_amount' => isset($extra['document_fee_amount'])
                ? round((float) $extra['document_fee_amount'], 2)
                : (isset($data['document_price']) ? round((float) $data['document_price'], 2) : null),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->student($studentId);
    }

    public function findStudentByPhone(string $phone): ?stdClass
    {
        return DB::table('students')->where('phone', $phone)->first();
    }

    public function findOrCreatePublicStudent(array $data): stdClass
    {
        return $this->findStudentByPhone($data['phone'])
            ?? $this->createStudent($data);
    }

    public function createEnrollment(array $data): stdClass
    {
        $now = now();
        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'study_class_id' => $data['study_class_id'],
            'student_id' => $data['student_id'],
            'enrollment_status' => $data['enrollment_status'] ?? 'active',
            'payment_status' => $data['payment_status'] ?? 'unpaid',
            'source' => $data['source'] ?? null,
            'fee_amount' => $data['fee_amount'] ?? 0,
            'document_fee_amount' => $data['document_fee_amount'] ?? 0,
            'amount_paid' => $data['amount_paid'] ?? 0,
            'enrolled_at' => $data['enrolled_at'] ?? $now,
            'paid_at' => $data['paid_at'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->enrollment($enrollmentId);
    }

    public function createPendingEnrollment(array $data): stdClass
    {
        return $this->createEnrollment(array_merge([
            'enrollment_status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'qr_code',
            'amount_paid' => 0,
        ], $data));
    }

    public function ensureClassHasSeat(StudyClass|stdClass $studyClass, string $field = 'student_id'): void
    {
        if ($this->activeEnrollmentCount((int) $studyClass->id) >= (int) $studyClass->capacity) {
            throw ValidationException::withMessages([
                $field => 'This class is full.',
            ]);
        }
    }

    // Force-enrolling past capacity is a deliberate override (see EnrollStudent),
    // not an error case - bump capacity to fit instead of leaving the class
    // permanently reading as over 100% filled.
    public function expandCapacityToFit(StudyClass|stdClass $studyClass): void
    {
        $seatsNeeded = $this->activeEnrollmentCount((int) $studyClass->id) + 1;

        if ($seatsNeeded > (int) $studyClass->capacity) {
            DB::table('study_classes')->where('id', $studyClass->id)->update([
                'capacity' => $seatsNeeded,
                'updated_at' => now(),
            ]);
        }
    }

    public function ensureStudentIsNotEnrolledInClass(int $studyClassId, int $studentId): void
    {
        if ($this->activeEnrollmentExistsForClass($studyClassId, $studentId)) {
            throw ValidationException::withMessages([
                'student_id' => 'This student is already enrolled in this class.',
            ]);
        }
    }

    public function ensureStudentHasNoPendingOrActiveEnrollment(int $studyClassId, int $studentId): void
    {
        if (DB::table('student_enrollments')
            ->where('study_class_id', $studyClassId)
            ->where('student_id', $studentId)
            ->whereIn('enrollment_status', ['pending', 'active'])
            ->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'This student already has a request for that class.',
            ]);
        }
    }

    public function activeEnrollmentExistsForClass(int $studyClassId, int $studentId): bool
    {
        return DB::table('student_enrollments')
            ->where('study_class_id', $studyClassId)
            ->where('enrollment_status', 'active')
            ->where('student_id', $studentId)
            ->exists();
    }

    public function activeEnrollmentExistsForCourseSchedule(int $studentId, int $courseId, int $termId, int $timeId): bool
    {
        return DB::table('student_enrollments')
            ->join('study_classes', 'study_classes.id', '=', 'student_enrollments.study_class_id')
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.enrollment_status', 'active')
            ->where('study_classes.course_id', $courseId)
            ->where('study_classes.term_id', $termId)
            ->where('study_classes.time_id', $timeId)
            ->exists();
    }

    public function lockStudyClass(int $studyClassId): stdClass
    {
        $studyClass = DB::table('study_classes')->where('id', $studyClassId)->lockForUpdate()->first();

        abort_unless($studyClass, 404);

        return $studyClass;
    }

    private function activeEnrollmentCount(int $studyClassId): int
    {
        return DB::table('student_enrollments')
            ->where('study_class_id', $studyClassId)
            ->where('enrollment_status', 'active')
            ->count();
    }

    private function student(int $studentId): stdClass
    {
        return DB::table('students')->where('id', $studentId)->first();
    }

    private function enrollment(int $enrollmentId): stdClass
    {
        return DB::table('student_enrollments')->where('id', $enrollmentId)->first();
    }
}
