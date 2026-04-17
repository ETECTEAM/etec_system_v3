<?php

namespace Database\Seeders\Permission;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $superAdmin->forceFill([
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
        ])->save();

        $superAdmin->syncRoles(['super_admin']);

        $roles = Role::query()
            ->where('guard_name', 'sanctum')
            ->whereIn('name', ['admin', 'instructor', 'student'])
            ->pluck('name')
            ->all();

        User::factory(100)
            ->create()
            ->each(function (User $user) use ($roles): void {
                $user->syncRoles([$roles[array_rand($roles)]]);
            });
    }
}
