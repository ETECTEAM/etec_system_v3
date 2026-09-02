<?php

namespace Database\Seeders\Dev;

use App\Models\InstructorData;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Modules\Instructor\Services\InstructorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dev-only. Creates one dedicated instructor per active WorkSchedule, so
 * scheduling / availability features can be exercised against every shift
 * shape (full-time and part-time).
 *
 * This is the only source of dev instructors - the old round-robin batch in
 * Core\UserSeeder is no longer part of the dev run (see DevSeeder).
 *
 * Logins are instructor1@etec.com .. instructorN@etec.com, numbered in
 * work-schedule id order. Idempotent: keyed by email, so re-running updates
 * in place and adding an 11th work schedule adds instructor11 on the next run.
 */
class InstructorWorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $schedules = WorkSchedule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->values();

        if ($schedules->isEmpty()) {
            $this->command?->warn('InstructorWorkScheduleSeeder: no active work schedules - run WorkScheduleSeeder first. Skipped.');

            return;
        }

        $specializations = SubCategory::query()->pluck('id')->all();
        $profileService = app(InstructorProfileService::class);

        foreach ($schedules as $index => $schedule) {
            $number = $index + 1;
            $displayName = "Instructor {$number} · {$schedule->name}";
            $employmentType = Str::startsWith($schedule->code, 'part_time') ? 'part_time' : 'full_time';

            $user = User::updateOrCreate(
                ['email' => "instructor{$number}@etec.com"],
                [
                    'name' => $displayName,
                    'password' => Hash::make('password'),
                    'role' => 'instructor',
                    'status' => 'active',
                ],
            );

            $user->syncRoles(['instructor']);

            $instructor = InstructorData::firstOrNew(['user_id' => $user->id]);

            $instructor->fill([
                'full_name' => $displayName,
                'phone' => '097'.str_pad((string) $number, 7, '0', STR_PAD_LEFT),
                'specialization' => $specializations === [] ? null : [$specializations[$index % count($specializations)]],
                'employment_type' => $employmentType,
                'shift_group' => $schedule->code,
                'work_schedule_id' => $schedule->id,
                'available_for_class' => true,
                'status' => true,
            ]);

            if (! $instructor->exists) {
                $instructor->instructor_code = InstructorService::generateInstructorCode();
            }

            $instructor->save();

            // Rebuilds InstructorAvailability rows from the work schedule's
            // (day_of_week, time_id) grid. Safe to call repeatedly.
            $profileService->generateInstructorAvailabilities($instructor->fresh());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info("InstructorWorkScheduleSeeder: seeded {$schedules->count()} instructors, one per active work schedule.");
    }
}
