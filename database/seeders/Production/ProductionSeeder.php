<?php

namespace Database\Seeders\Production;

use Database\Seeders\Building\BuildingSeeder;
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Course\CategorySeeder;
use Database\Seeders\Course\CourseSeeder;
use Database\Seeders\Course\CourseTrackSeeder;
use Database\Seeders\Course\SubCategorySeeder;
use Database\Seeders\Dev\DevSeeder;
use Database\Seeders\Dev\InstructorWorkScheduleSeeder;
use Database\Seeders\GradingSettingSeeder;
use Database\Seeders\LoginLockoutSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\DashboardPermissionSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Schedule\ScheduleSeeder;
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;
use Database\Seeders\Website\WebsiteMenuSeeder;
use Database\Seeders\WorkSchedule\WorkScheduleSeeder;
use Illuminate\Database\Seeder;

/**
 * The full seed set for every environment.
 *
 * Sections 1-7 are reference / lookup data + the real super-admin and are safe
 * on a live database. Section 8 folds in \Database\Seeders\Dev\DevSeeder
 * (admin login, one instructor per work schedule, every course opened with
 * flat pricing) so a plain `db:seed` produces the same working dataset
 * everywhere - remove that call to go back to reference-data-only prod seeds.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Permissions + roles + role/permission assignments
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            // dashboard.view + instructor_profile / work_schedule perms - without
            // this every instructor 403s on /dashboard.
            DashboardPermissionSeeder::class,

            // 2. The one real login (idempotent, never truncates users)
            SuperAdminSeeder::class,

            // 3. Application settings
            LoginLockoutSeeder::class,
            GradingSettingSeeder::class,

            // 4. Course taxonomy
            CategorySeeder::class,
            SubCategorySeeder::class,

            // 5. Scheduling reference data (Time + WorkSchedule are needed
            //    before instructor shifts can be assigned in DevSeeder)
            ClassTypeSeeder::class,
            TermSeeder::class,
            TimeSeeder::class,
            WorkScheduleSeeder::class,
            BuildingSeeder::class,

            // 6. Courses + schedule grid
            CourseTrackSeeder::class,
            CourseSeeder::class,
            ScheduleSeeder::class,

            InstructorWorkScheduleSeeder::class,

            // 7. Public website defaults
            // WebsiteMenuSeeder::class,

            // 8. Demo data - admin login, instructors, course enroll config.
            //    Drop this line for a reference-data-only production seed.
            DevSeeder::class,
        ]);
    }
}
