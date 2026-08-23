<?php

namespace Database\Seeders\Core;

use App\Models\InstructorData;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Modules\Instructor\Services\InstructorProfileService;
use App\Modules\Instructor\Services\InstructorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        User::truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('instructor_availabilities')->truncate();
        InstructorData::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->createAdmins();
        $this->createInstructors();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function createAdmins(): void
    {
        $admins = [
            ['name' => 'Super Admin', 'email' => 'superadmin@etec.com', 'role' => 'super_admin'],
            ['name' => 'Admin User',  'email' => 'admin@etec.com',      'role' => 'admin'],
        ];

        foreach ($admins as $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
                'role'     => $data['role'],
                'status'   => 'active',
            ]);

            $user->syncRoles([$data['role']]);
        }
    }

    private function createInstructors(): void
    {
        $specializations = SubCategory::pluck('id')->toArray();

        // Round-robin across every active work schedule (full-time and
        // part-time) so each instructor exercises a different shift shape.
        $schedules = WorkSchedule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'code']);

        $availabilityService = app(InstructorProfileService::class);

        $instructors = [
            ['name' => 'John Doe',           'email' => 'instructor@etec.com',     'phone' => '012345678'],
            ['name' => 'Jane Smith',         'email' => 'instructor2@etec.com',    'phone' => '012345679'],
            ['name' => 'Dara Sokha',         'email' => 'instructor3@etec.com',    'phone' => '012345680'],
            ['name' => 'Sok Vannak',         'email' => 'instructor4@etec.com',    'phone' => '012345681'],
            ['name' => 'Chheng Dara',        'email' => 'instructor5@etec.com',    'phone' => '012345682'],
            ['name' => 'Lim Piseth',         'email' => 'instructor6@etec.com',    'phone' => '012345683'],
            ['name' => 'Keo Bunny',          'email' => 'instructor7@etec.com',    'phone' => '012345684'],
            ['name' => 'Srey Neang',         'email' => 'instructor8@etec.com',    'phone' => '012345685'],
            ['name' => 'Pov Rattana',        'email' => 'instructor9@etec.com',    'phone' => '012345686'],
            ['name' => 'Bopha Kem',          'email' => 'instructor10@etec.com',   'phone' => '012345687'],
        ];

        foreach ($instructors as $index => $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
                'role'     => 'instructor',
                'status'   => 'active',
            ]);

            $user->syncRoles(['instructor']);

            $schedule = $schedules->isEmpty() ? null : $schedules[$index % $schedules->count()];

            $instructorData = InstructorData::create([
                'user_id'            => $user->id,
                'full_name'          => $data['name'],
                'instructor_code'    => InstructorService::generateInstructorCode(),
                'phone'              => $data['phone'],
                'specialization'     => [$specializations[$index % count($specializations)]],
                'employment_type'    => $schedule !== null && str_starts_with($schedule->code, 'part_time') ? 'part_time' : 'full_time',
            ]);

            if ($schedule !== null) {
                $instructorData->update(['work_schedule_id' => $schedule->id]);
                $availabilityService->generateInstructorAvailabilities($instructorData->fresh());
            }
        }
    }
}