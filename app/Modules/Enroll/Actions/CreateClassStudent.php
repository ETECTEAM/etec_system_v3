<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateClassStudent
{
    public function handle(StudyClass $studyClass, array $data): StudentEnrollment
    {
        return DB::transaction(function () use ($studyClass, $data): StudentEnrollment {
            $studyClass = StudyClass::query()->lockForUpdate()->findOrFail($studyClass->id);

            $activeCount = StudentEnrollment::query()
                ->where('study_class_id', $studyClass->id)
                ->where('enrollment_status', 'active')
                ->count();

            if ($activeCount >= $studyClass->capacity) {
                throw ValidationException::withMessages([
                    'name' => 'This class is full.',
                ]);
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $this->studentEmail(),
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            Student::create([
                'user_id' => $user->id,
                'full_name' => $data['name'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'student_status' => 'active',
            ]);

            return StudentEnrollment::create([
                'study_class_id' => $studyClass->id,
                'student_id' => $user->id,
                'enrollment_status' => 'active',
                'payment_status' => 'unpaid',
                'fee_amount' => $studyClass->price,
                'document_fee_amount' => $studyClass->document_price,
                'amount_paid' => 0,
                'enrolled_at' => now(),
            ]);
        });
    }

    private function studentEmail(): string
    {
        do {
            $email = 'student-'.Str::lower(Str::random(16)).'@etec.local';
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }
}
