<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
