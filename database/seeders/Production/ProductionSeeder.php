<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;

// Permission / role data
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;

// Settings
use Database\Seeders\LoginLockoutSeeder;
use Database\Seeders\GradingSettingSeeder;

// Reference / lookup data
use Database\Seeders\Course\CategorySeeder;
use Database\Seeders\Course\SubCategorySeeder;
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;
use Database\Seeders\WorkSchedule\WorkScheduleSeeder;
use Database\Seeders\Building\BuildingSeeder;
use Database\Seeders\Course\CourseTrackSeeder;
use Database\Seeders\Course\CourseSeeder;
use Database\Seeders\Schedule\ScheduleSeeder;
use Database\Seeders\Website\WebsiteMenuSeeder;

/**
 * Seeders that are safe to run on a real production database.
 *
 * Only reference / lookup data + a single real super-admin login. No fake
 * users, no demo students, no scenario data - that all lives in
 * \Database\Seeders\Dev\DevSeeder.
 *
 * Runs on every environment (see DatabaseSeeder). Every seeder listed here
 * must be idempotent so a re-run on a live database is harmless.
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

            // 7. Public website defaults
            // WebsiteMenuSeeder::class,
        ]);
    }
}
