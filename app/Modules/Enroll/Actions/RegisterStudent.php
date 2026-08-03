<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pre-registers a student with no class assigned yet. They get enrolled into a
 * class later — e.g. via the "Enroll Existing Student" action on a class, or by
 * scanning the class's own QR self-registration code.
 */
class RegisterStudent
{
    private const DOCUMENT_FEE = 5;

    public function handle(array $data): Student
    {
        return DB::transaction(function () use ($data): Student {
            $user = User::create([
                'name' => $data['name'],
                'email' => $this->studentEmail(),
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            return Student::create([
                'user_id' => $user->id,
                'full_name' => $data['name'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'student_status' => 'active',
                'course_id' => $data['course_id'],
                'term_id' => $data['term_id'],
                'time_id' => $data['time_id'],
                'fee_amount' => round((float) $data['price'] + self::DOCUMENT_FEE, 2),
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
