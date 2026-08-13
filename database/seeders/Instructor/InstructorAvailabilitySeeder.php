<?php

namespace Database\Seeders\Instructor;

use App\Models\InstructorData;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Modules\Instructor\Services\InstructorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstructorAvailabilitySeeder extends Seeder
{
    private const SPECIALIZATIONS = [
        'Information Technology',
        'Web Development',
        'Mobile Development',
        'Programming',
        'Networking',
        'General',
    ];

    /**
     * One instructor per shift template, each with real InstructorAvailability
     * rows generated from that template's blocks — so every day/time combo a
     * shift template covers has at least one available_for_class instructor
     * behind it, and RegisterStudentForSchedule::availableInstructor() always
     * has someone to pick from during manual testing.
     *
     * Depends on Schedule\ShiftTemplateSeeder and Permission\RoleSeeder having
     * run first (shift templates + the 'instructor' role must already exist).
     */
    public function run(): void
    {
        $service = app(InstructorProfileService::class);
        $templates = ShiftTemplate::with('blocks')->get();

        foreach ($templates as $index => $template) {
            $email = "instructor.{$template->code}@etec.com";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Instructor {$template->name}",
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ],
            );

            $user->assignRole('instructor');

            $instructor = InstructorData::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $user->name,
                    'instructor_code' => InstructorData::where('user_id', $user->id)->value('instructor_code')
                        ?? InstructorService::generateInstructorCode(),
                    'phone' => '0100000'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'specialization' => self::SPECIALIZATIONS[$index % count(self::SPECIALIZATIONS)],
                    'employment_type' => $template->employment_type,
                    'shift_group' => $template->code,
                    'shift_template_id' => $template->id,
                    'available_for_class' => true,
                    'status' => true,
                ],
            );

            $service->generateInstructorAvailabilities($instructor);
        }
    }
}
