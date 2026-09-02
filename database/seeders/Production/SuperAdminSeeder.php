<?php

namespace Database\Seeders\Production;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the single real super-admin login for a production database.
 *
 * Idempotent and non-destructive: it never truncates `users`, so running it
 * again on a live database only tops up the account / role if missing.
 *
 * Credentials come from env so a real deploy is not stuck with the default
 * password:
 *   SEEDER_SUPERADMIN_EMAIL    (default superadmin@etec.com)
 *   SEEDER_SUPERADMIN_NAME     (default "Super Admin")
 *   SEEDER_SUPERADMIN_PASSWORD (default "password" - change this in prod!)
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $email = env('SEEDER_SUPERADMIN_EMAIL', 'superadmin@etec.com');
        $name = env('SEEDER_SUPERADMIN_NAME', 'Super Admin');
        $password = env('SEEDER_SUPERADMIN_PASSWORD', 'password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'status' => 'active',
            ],
        );

        // Keep the role column / spatie role in sync even if the row already existed.
        if ($user->role !== 'super_admin' || $user->status !== 'active') {
            $user->update(['role' => 'super_admin', 'status' => 'active']);
        }

        if (! $user->hasRole('super_admin')) {
            $user->syncRoles(['super_admin']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
