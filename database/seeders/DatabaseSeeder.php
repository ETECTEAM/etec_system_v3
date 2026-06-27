<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< Updated upstream
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;

=======
use Database\Seeders\Course\CategorySeeder;
use Database\Seeders\Course\SubCategorySeeder;
use Database\Seeders\Course\CourseTrackSeeder;
use Database\Seeders\Course\CourseSeeder;
use Database\Seeders\Course\CourseLessonSeeder;
>>>>>>> Stashed changes
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
<<<<<<< Updated upstream
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
=======
            CoreSeeder::class,
            ClassTypeSeeder::class,
            CategorySeeder::class,
            SubCategorySeeder::class,
            CourseTrackSeeder::class,
            CourseSeeder::class,
            CourseLessonSeeder::class,
>>>>>>> Stashed changes
        ]);
    }
}