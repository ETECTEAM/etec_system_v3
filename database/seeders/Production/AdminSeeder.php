<?php

namespace Database\Seeders\Production;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the single real admin (non-super) login for a production database.
 *
 * Idempotent and non-destructive: it never truncates `users`, so running it
 * again on a live database only tops up the account / role if missing.
 *
 * Credentials come from env so a real deploy is not stuck with the default
 * password:
 *   SEEDER_ADMIN_EMAIL    (default admin@etec.com)
 *   SEEDER_ADMIN_NAME     (default "Admin User")
 *   SEEDER_ADMIN_PASSWORD (default "password" - change this in prod!)
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $email = env('SEEDER_ADMIN_EMAIL', 'admin@etec.com');
        $name = env('SEEDER_ADMIN_NAME', 'Admin User');
        $password = env('SEEDER_ADMIN_PASSWORD', '6ny\6!W4b6{<');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
            ],
        );

        // Keep the role column / spatie role in sync even if the row already existed.
        if ($user->role !== 'admin' || $user->status !== 'active') {
            $user->update(['role' => 'admin', 'status' => 'active']);
        }

        if (! $user->hasRole('admin')) {
            $user->syncRoles(['admin']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
