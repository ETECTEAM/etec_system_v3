<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\UserSeeder;
use Database\Seeders\Class\ClassTypeSeeder;
use Database\Seeders\Class\ClassCategorySeeder;
use Database\Seeders\Core\AssignPermissionSeeder as CoreAssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder as CorePermissionSeeder;
use Database\Seeders\Core\RoleSeeder as CoreRoleSeeder;
use Database\Seeders\Core\UserSeeder as CoreUserSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CorePermissionSeeder::class,
            CoreRoleSeeder::class,
            CoreAssignPermissionSeeder::class,
            CoreUserSeeder::class,
            ClassTypeSeeder::class,
            ClassCategorySeeder::class,
        ]);
    }
}
