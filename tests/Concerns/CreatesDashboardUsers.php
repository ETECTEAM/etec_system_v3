<?php

namespace Tests\Concerns;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;

/**
 * Creates active, role-assigned dashboard staff for admin/super_admin CRUD tests.
 */
trait CreatesDashboardUsers
{
    // Seeds the actual permission set the app runs in production (see
    // database/seeders/Production/ProductionSeeder.php), not the unused
    // Database\Seeders\Core\* set - routes/policies gated by a specific
    // permission (e.g. view-users, view-classes) need the real grants to
    // pass for an 'admin' user, since only super_admin bypasses via
    // Gate::before.
    protected function seedRoles(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, AssignPermissionSeeder::class]);
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole($role);

        return $user;
    }

    protected function superAdmin(): User
    {
        return $this->userWithRole('super_admin');
    }

    protected function admin(): User
    {
        return $this->userWithRole('admin');
    }

    protected function instructor(): User
    {
        return $this->userWithRole('instructor');
    }
}
