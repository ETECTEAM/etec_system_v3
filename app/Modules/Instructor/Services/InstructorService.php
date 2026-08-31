<?php

namespace App\Modules\Instructor\Services;

use App\Models\InstructorData;
use App\Models\User;

class InstructorService
{
    public static function generateInstructorCode(): string
    {
        $max = InstructorData::where('instructor_code', 'like', 'ETEC-%')
            ->pluck('instructor_code')
            ->map(fn (string $code): int => (int) substr($code, 5))
            ->max() ?? 0;

        return 'ETEC-'.str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    public function syncProfile(User $user, string $role, array $student, array $instructorData): void
    {
        if ($role === 'student') {
            $user->instructorData()->delete();
            $user->student()->updateOrCreate(['user_id' => $user->id], $student);

            return;
        }
        if ($role === 'instructor') {
            $user->student()->delete();
            if (empty($instructorData['instructor_code'])) {
                $instructorData['instructor_code'] = self::generateInstructorCode();
            }
            $user->instructorData()->updateOrCreate(['user_id' => $user->id], $instructorData);

            return;
        }
        $user->student()->delete();
        $user->instructorData()->delete();
    }
}
