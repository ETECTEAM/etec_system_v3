<?php

namespace Database\Seeders;

use App\Models\Time;
use Illuminate\Database\Seeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Class\ClassCategorySeeder;

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
        ]);
    }
}