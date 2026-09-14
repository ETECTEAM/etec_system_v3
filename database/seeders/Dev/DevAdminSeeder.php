<?php

namespace Database\Seeders\Dev;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dev-only. The plain `admin` account (role: admin) for clicking through the
 * app locally.
 *
 * The super-admin login is NOT created here - that is
 * \Database\Seeders\Production\SuperAdminSeeder, which always runs first via
 * DatabaseSeeder / `composer seed-prod`.
 *
 * Idempotent: updateOrCreate keyed by email, never truncates.
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::updateOrCreate(
            ['email' => 'admin@etec.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ],
        );

        $user->syncRoles(['admin']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
