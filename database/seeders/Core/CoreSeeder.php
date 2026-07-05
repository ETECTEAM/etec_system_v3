<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->truncateTables();

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
            UserSeeder::class,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function truncateTables(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();

        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('users')->truncate();

        Schema::enableForeignKeyConstraints();
    }
}