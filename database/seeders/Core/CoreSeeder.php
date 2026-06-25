<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate all tables to avoid duplicate entries
        DB::statement('TRUNCATE TABLE model_has_permissions RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE model_has_roles RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE role_has_permissions RESTART IDENTITY CASCADE');

        DB::statement('TRUNCATE TABLE permissions RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE roles RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');

        // Seed the database with initial data
        $this->call([
            PermissionSeeder::class, // Seed permissions first
            RoleSeeder::class, // Seed roles next
            AssignPermissionSeeder::class, // Assign permissions to roles
            UserSeeder::class, // Seed users last
        ]);
    }
}