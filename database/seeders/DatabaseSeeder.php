<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Core\CoreSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Class\ClassSeeder;
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;
use Database\Seeders\Schedule\ScheduleSeeder;
use Database\Seeders\Class\ClassListSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ១. បង្កើតរចនាសម្ព័ន្ធសិទ្ធិ និងគណនីមុនគេ (លំដាប់ត្រឹមត្រូវ)
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,

            // ២. បង្កើតទិន្នន័យគ្រឹះ (Core & Base Data)
            CoreSeeder::class,
            ClassTypeSeeder::class,
            
            TermSeeder::class,
            TimeSeeder::class,

            // ៣. បង្កើតទិន្នន័យចងភ្ជាប់លម្អិត (Relation Data) ដែលត្រូវការទិន្នន័យខាងលើមកប្រើ
            ClassSeeder::class,
            ScheduleSeeder::class,
            ClassListSeeder::class, // បន្ថែមក្បៀសនៅចុងបន្ទាត់ខាងលើ រួចរៀបមកនៅខាងក្រោមគេវិញ
        ]);
    }
}