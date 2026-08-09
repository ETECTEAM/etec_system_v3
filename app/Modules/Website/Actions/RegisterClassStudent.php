<?php

namespace App\Modules\Website\Actions;

use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use App\Modules\Notification\Events\NotificationsUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Public, no-login self-registration from the /classes page — same shape as
 * the QR self-registration flow (Enroll\Actions\CreateClassStudent), except
 * the enrollment is tagged source=public_website so the dashboard can list it,
 * and a dashboard Notification is raised to flag it for follow-up/payment.
 */
class RegisterClassStudent
{
    public function handle(StudyClass $studyClass, array $data): StudentEnrollment
    {
        $enrollment = DB::transaction(function () use ($studyClass, $data): StudentEnrollment {
            $studyClass = StudyClass::query()->lockForUpdate()->findOrFail($studyClass->id);

            $activeCount = $studyClass->enrollments()
                ->where('enrollment_status', 'active')
                ->count();

            if ($activeCount >= $studyClass->capacity) {
                throw ValidationException::withMessages([
                    'name' => 'This class is full.',
                ]);
            }

            $student = Student::query()->where('phone', $data['phone'])->first();

            if ($student !== null) {
                $alreadyEnrolled = StudentEnrollment::query()
                    ->where('study_class_id', $studyClass->id)
                    ->where('enrollment_status', 'active')
                    ->where('student_id', $student->user_id)
                    ->exists();

                if ($alreadyEnrolled) {
                    throw ValidationException::withMessages([
                        'phone' => 'This phone number is already registered for this class.',
                    ]);
                }
            }

            if ($student === null) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $this->studentEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                    'status' => 'active',
                ]);

                $student = Student::create([
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'gender' => $data['gender'],
                    'phone' => $data['phone'],
                    'student_status' => 'active',
                ]);
            }

            $enrollment = StudentEnrollment::create([
                'study_class_id' => $studyClass->id,
                'student_id' => $student->user_id,
                'enrollment_status' => 'active',
                'payment_status' => 'unpaid',
                'source' => 'public_website',
                'fee_amount' => $studyClass->price,
                'document_fee_amount' => $studyClass->document_price,
                'amount_paid' => 0,
                'enrolled_at' => now(),
            ]);

            Notification::create([
                'title' => 'New Class Registration',
                'message' => "{$data['name']} registered for \"{$studyClass->title}\".",
                'type' => 'class_registration',
            ]);

            return $enrollment;
        });

        NotificationsUpdated::dispatch();

        return $enrollment;
    }

    private function studentEmail(): string
    {
        do {
            $email = 'student-'.Str::lower(Str::random(16)).'@etec.local';
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }
}
