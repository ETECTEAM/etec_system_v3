<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< HEAD
<<<<<<< HEAD
use Database\Seeders\Core\CoreSeeder;
=======
>>>>>>> 373cb1b91626d3367ed07e987fdef024083cfc6c
=======
<<<<<<< Updated upstream
>>>>>>> dev
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;
use Database\Seeders\Class\ClassTypeSeeder;
<<<<<<< HEAD
use Database\Seeders\Class\ClassSeeder;
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;
use Database\Seeders\Schedule\ScheduleSeeder;
use Database\Seeders\Class\ClassListSeeder;
=======
use Database\Seeders\Class\ClassCategorySeeder;
use Database\Seeders\Core\AssignPermissionSeeder as CoreAssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder as CorePermissionSeeder;
use Database\Seeders\Core\RoleSeeder as CoreRoleSeeder;
use Database\Seeders\Core\UserSeeder as CoreUserSeeder;
>>>>>>> 373cb1b91626d3367ed07e987fdef024083cfc6c

=======
use Database\Seeders\Course\CategorySeeder;
use Database\Seeders\Course\SubCategorySeeder;
use Database\Seeders\Course\CourseTrackSeeder;
use Database\Seeders\Course\CourseSeeder;
use Database\Seeders\Course\CourseLessonSeeder;
>>>>>>> Stashed changes
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
<<<<<<< HEAD
<<<<<<< HEAD
            // ១. បង្កើតរចនាសម្ព័ន្ធសិទ្ធិ និងគណនីមុនគេ (លំដាប់ត្រឹមត្រូវ)
=======
<<<<<<< Updated upstream
>>>>>>> dev
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
<<<<<<< HEAD

            // ២. បង្កើតទិន្នន័យគ្រឹះ (Core & Base Data)
            CoreSeeder::class,
            ClassTypeSeeder::class,
            
            TermSeeder::class,
            TimeSeeder::class,

            // ៣. បង្កើតទិន្នន័យចងភ្ជាប់លម្អិត (Relation Data) ដែលត្រូវការទិន្នន័យខាងលើមកប្រើ
            ClassSeeder::class,
            ScheduleSeeder::class,
            ClassListSeeder::class, // បន្ថែមក្បៀសនៅចុងបន្ទាត់ខាងលើ រួចរៀបមកនៅខាងក្រោមគេវិញ
=======
            CorePermissionSeeder::class,
            CoreRoleSeeder::class,
            CoreAssignPermissionSeeder::class,
            CoreUserSeeder::class,
            ClassTypeSeeder::class,
            ClassCategorySeeder::class,
>>>>>>> 373cb1b91626d3367ed07e987fdef024083cfc6c
=======
=======
            CoreSeeder::class,
            ClassTypeSeeder::class,
            CategorySeeder::class,
            SubCategorySeeder::class,
            CourseTrackSeeder::class,
            CourseSeeder::class,
            CourseLessonSeeder::class,
>>>>>>> Stashed changes
>>>>>>> dev
        ]);
    }
}