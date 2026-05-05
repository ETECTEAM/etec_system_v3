<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionManagementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
        ]);
    }

    public function test_super_admin_can_create_role(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/admin/roles', [
                'name' => 'editor',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Role created successfully.')
            ->assertJsonPath('role.name', 'editor')
            ->assertJsonPath('role.guard_name', 'sanctum');

        $this->assertDatabaseHas('roles', [
            'name' => 'editor',
            'guard_name' => 'sanctum',
        ]);
    }

    public function test_super_admin_can_create_feature_permissions_with_default_actions(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/admin/features', [
                'name' => 'teacher',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Feature permissions created successfully.')
            ->assertJsonPath('feature', 'teacher')
            ->assertJsonFragment(['teacher.view'])
            ->assertJsonFragment(['teacher.create'])
            ->assertJsonFragment(['teacher.update'])
            ->assertJsonFragment(['teacher.delete']);

        $this->assertDatabaseHas('permissions', [
            'name' => 'teacher.view',
            'guard_name' => 'sanctum',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'teacher.create',
            'guard_name' => 'sanctum',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'teacher.update',
            'guard_name' => 'sanctum',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'teacher.delete',
            'guard_name' => 'sanctum',
        ]);
    }

    public function test_super_admin_can_create_feature_permissions_with_custom_actions(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/admin/features', [
                'name' => 'teacher',
                'actions' => ['view', 'manage'],
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Feature permissions created successfully.')
            ->assertJsonPath('feature', 'teacher')
            ->assertJsonFragment(['teacher.view'])
            ->assertJsonFragment(['teacher.manage']);

        $this->assertDatabaseHas('permissions', [
            'name' => 'teacher.manage',
            'guard_name' => 'sanctum',
        ]);
    }

    public function test_super_admin_can_create_permission(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/admin/permissions', [
                'name' => 'report.view',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Permission created successfully.')
            ->assertJsonPath('permission.name', 'report.view')
            ->assertJsonPath('permission.guard_name', 'sanctum');

        $this->assertDatabaseHas('permissions', [
            'name' => 'report.view',
            'guard_name' => 'sanctum',
        ]);
    }

    public function test_super_admin_can_assign_permissions_to_role(): void
    {
        $superAdmin = $this->createSuperAdmin();
        Role::findOrCreate('editor', 'sanctum');

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/admin/roles/assign-permissions', [
                'role_name' => 'editor',
                'permissions' => ['course.view', 'course.update'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Permissions assigned to role successfully.')
            ->assertJsonPath('role', 'editor')
            ->assertJsonFragment(['course.view'])
            ->assertJsonFragment(['course.update']);

        $this->assertTrue(Role::findByName('editor', 'sanctum')->hasPermissionTo('course.view'));
        $this->assertTrue(Role::findByName('editor', 'sanctum')->hasPermissionTo('course.update'));
    }

    public function test_super_admin_can_assign_role_to_user(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $user = User::factory()->create();
        Role::findOrCreate('editor', 'sanctum');

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/admin/users/{$user->id}/assign-role", [
                'role_name' => 'editor',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Role assigned to user successfully.')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('roles.0', 'editor');

        $this->assertTrue($user->fresh()->hasRole('editor'));
    }

    public function test_super_admin_can_assign_permissions_directly_to_user(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/admin/users/{$user->id}/assign-permissions", [
                'permissions' => ['course.view', 'enrollment.view'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Permissions assigned to user successfully.')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonFragment(['course.view'])
            ->assertJsonFragment(['enrollment.view']);

        $freshUser = $user->fresh();
        $this->assertTrue($freshUser->hasDirectPermission('course.view'));
        $this->assertTrue($freshUser->hasDirectPermission('enrollment.view'));
    }

    public function test_non_super_admin_cannot_access_permission_management_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/roles', [
                'name' => 'editor',
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_access_permission_management_api(): void
    {
        $this->postJson('/api/admin/roles', [
            'name' => 'editor',
        ])->assertUnauthorized();
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }
}
