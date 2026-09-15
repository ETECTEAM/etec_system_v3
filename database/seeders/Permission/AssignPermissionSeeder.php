<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // --- សម្រាប់ WEB GUARD ---
        $superAdminWeb = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        $adminWeb = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $instructorWeb = Role::where('name', 'instructor')->where('guard_name', 'web')->first();

        $allPermissionsWeb = Permission::where('guard_name', 'web')->get();

        // super_admin ទទួលបានសិទ្ធិទាំងអស់
        $superAdminWeb->syncPermissions($allPermissionsWeb);
        // admin អាចមើល និងដោះសោគណនីដែលត្រូវបានទប់ស្កាត់
        // Plus the Users/Classes/Terms/Times CRUD permissions - these routes
        // used to be role-only (role:super_admin|admin), so admin already had
        // full access in practice. Granting them here preserves that exact
        // access now that the routes also check these permissions directly,
        // while letting a super_admin later revoke individual actions per
        // admin from the permission management pages.
        $adminWeb?->givePermissionTo([
            'unblock-login-accounts',
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-classes', 'create-classes', 'edit-classes', 'delete-classes',
            'view-terms', 'manage-terms',
            'view-times', 'manage-times',
        ]);
        // instructor ទទួលបានតែសិទ្ធិមើល និងបង្កើត Classes
        $instructorWeb->givePermissionTo(['view-classes', 'create-classes']);


        // --- សម្រាប់ SANCTUM GUARD (API) ---
        $superAdminSanctum = Role::where('name', 'super_admin')->where('guard_name', 'sanctum')->first();
        $allPermissionsSanctum = Permission::where('guard_name', 'sanctum')->get();
        
        $superAdminSanctum->syncPermissions($allPermissionsSanctum);
    }
}