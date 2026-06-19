<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Class\ClassCategorySeeder;
use Database\Seeders\Schedule\ScheduleSeeder;
use Database\Seeders\Term\TermSeeder;
use Database\Seeders\Time\TimeSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
            ClassTypeSeeder::class,
            ClassCategorySeeder::class,
            TermSeeder::class,
            TimeSeeder::class,
            ScheduleSeeder::class
        ]);
    }
}