<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie permission cache before reset.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Disable foreign key checks for MySQL.
        // This allows truncating tables that are linked by foreign keys.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate pivot tables first.
        // These tables connect users, roles, and permissions.
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();

        // Truncate main tables.
        // MySQL TRUNCATE automatically resets auto increment IDs.
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('users')->truncate();

        // Enable foreign key checks again.
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Run core seeders again.
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
        ]);

        // Clear Spatie permission cache after seeding.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}