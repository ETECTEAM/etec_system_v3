<?php

namespace Tests\Unit\OfficialLeave\Concerns;

use App\Models\OfficialLeave;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Minimal fixtures for the official-leave tests: an admin, a super admin, and
 * students. No class/enrollment scaffolding is needed because official leaves
 * are student-scoped (study_class_id is nullable).
 */
trait CreatesOfficialLeaveFixtures
{
    protected function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    protected function makeLeaveStudent(): Student
    {
        return Student::query()->create([
            'full_name' => 'Leave Student '.Str::random(6),
            'gender' => 'male',
            'phone' => '0'.random_int(10000000, 99999999),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeLeave(Student $student, array $overrides = []): OfficialLeave
    {
        return OfficialLeave::query()->create([
            'student_id' => $student->id,
            'study_class_id' => null,
            'start_date' => $overrides['start_date'] ?? now()->toDateString(),
            'end_date' => $overrides['end_date'] ?? now()->toDateString(),
            'reason' => $overrides['reason'] ?? 'Family matter',
            'status' => $overrides['status'] ?? OfficialLeave::STATUS_PENDING,
            'leave_request_session_id' => $overrides['leave_request_session_id'] ?? null,
        ]);
    }
}
