<?php

namespace Database\Seeders;

use App\Modules\Registration\Controllers\Enroll\StudentRegistrationController;
use Illuminate\Database\Seeder;

// Permission seeders
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;

// Core / base seeders
use Database\Seeders\Core\CoreSeeder;

// Building / Floor / Room seeders
use Database\Seeders\Building\BuildingSeeder;

// Course seeders
use Database\Seeders\Course\CategorySeeder;
use Database\Seeders\Course\SubCategorySeeder;
use Database\Seeders\Course\CourseTrackSeeder;
use Database\Seeders\Course\CourseSeeder;

// Class seeders
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Class\ClassSeeder;

// Other base seeders
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;
use Database\Seeders\Schedule\ScheduleSeeder;

// Shift template seeders
use Database\Seeders\Schedule\ShiftTemplateSeeder;
use Database\Seeders\Website\WebsiteMenuSeeder;

// Login security seeders
use Database\Seeders\LoginLockoutSeeder;

// Grading / attendance settings
use Database\Seeders\GradingSettingSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Permission + user first
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
            LoginLockoutSeeder::class,
            GradingSettingSeeder::class,

            // 2. Course data (needed for instructor specializations)
            CategorySeeder::class,
            SubCategorySeeder::class,

            // 3. Core/base data
            CoreSeeder::class,
            ClassTypeSeeder::class,
            TermSeeder::class,
            TimeSeeder::class,
            BuildingSeeder::class,

            // 4. Remaining course data
            CourseTrackSeeder::class,
            CourseSeeder::class,

            // 5. Class relation data
            ClassSeeder::class,
            ScheduleSeeder::class,

            // 6. Shift templates
            ShiftTemplateSeeder::class,

            // 7. Public website defaults
            WebsiteMenuSeeder::class,
        ]);
    }
}
