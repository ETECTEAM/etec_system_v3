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
        $instructorWeb = Role::where('name', 'instructor')->where('guard_name', 'web')->first();
        
        $allPermissionsWeb = Permission::where('guard_name', 'web')->get();
        
        // super_admin ទទួលបានសិទ្ធិទាំងអស់
        $superAdminWeb->syncPermissions($allPermissionsWeb);
        // instructor ទទួលបានតែសិទ្ធិមើល និងបង្កើត Classes
        $instructorWeb->givePermissionTo(['view-classes', 'create-classes']);


        // --- សម្រាប់ SANCTUM GUARD (API) ---
        $superAdminSanctum = Role::where('name', 'super_admin')->where('guard_name', 'sanctum')->first();
        $allPermissionsSanctum = Permission::where('guard_name', 'sanctum')->get();
        
        $superAdminSanctum->syncPermissions($allPermissionsSanctum);
    }
}