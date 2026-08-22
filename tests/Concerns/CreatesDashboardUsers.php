<?php

namespace Tests\Concerns;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\Core\RoleSeeder;

/**
 * Creates active, role-assigned dashboard staff for admin/super_admin CRUD tests.
 */
trait CreatesDashboardUsers
{
    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
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
