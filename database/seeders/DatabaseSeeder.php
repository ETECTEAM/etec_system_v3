<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Permission seeders
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;

// Core / base seeders
use Database\Seeders\Core\CoreSeeder;

// Course seeders
use Database\Seeders\Course\CategorySeeder;
use Database\Seeders\Course\SubCategorySeeder;
use Database\Seeders\Course\CourseTrackSeeder;
use Database\Seeders\Course\CourseSeeder;
use Database\Seeders\Course\CourseLessonSeeder;

// Class seeders
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Class\ClassSeeder;
use Database\Seeders\Class\ClassListSeeder;

// Other base seeders
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;
use Database\Seeders\Schedule\ScheduleSeeder;

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

            // 2. Core/base data
            CoreSeeder::class,
            ClassTypeSeeder::class,
            TermSeeder::class,
            TimeSeeder::class,

            // 3. Course data
            CategorySeeder::class,
            SubCategorySeeder::class,
            CourseTrackSeeder::class,
            CourseSeeder::class,
            CourseLessonSeeder::class,

            // 4. Class relation data
            ClassSeeder::class,
            ScheduleSeeder::class,
            ClassListSeeder::class,
        ]);
    }
}