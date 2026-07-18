<?php

namespace Tests\Feature\User;

use App\Models\User;
use Database\Seeders\Core\AssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignPermissionSeeder::class,
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function instructor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        return $user;
    }

    private function webRole(string $name): Role
    {
        return Role::where('name', $name)->where('guard_name', 'web')->firstOrFail();
    }

    // GET /dashboard/users/roles

    public function test_super_admin_can_view_roles_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/users/roles')
            ->assertOk();
    }

    public function test_admin_can_view_roles_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/users/roles')
            ->assertOk();
    }

    public function test_instructor_cannot_view_roles_page(): void
    {
        $this->actingAs($this->instructor())
            ->get('/dashboard/users/roles')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_roles_page(): void
    {
        $this->get('/dashboard/users/roles')
            ->assertRedirect('/login');
    }

    // POST /dashboard/users/roles

    public function test_super_admin_can_create_role(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/users/roles', ['name' => 'Editor'])
            ->assertRedirect('/dashboard/users/roles');

        $this->assertDatabaseHas('roles', [
            'name' => 'editor',
            'guard_name' => 'sanctum',
        ]);
    }

    public function test_admin_can_create_role(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/users/roles', ['name' => 'coordinator'])
            ->assertRedirect('/dashboard/users/roles');

        $this->assertDatabaseHas('roles', ['name' => 'coordinator']);
    }

    public function test_instructor_cannot_create_role(): void
    {
        $this->actingAs($this->instructor())
            ->post('/dashboard/users/roles', ['name' => 'editor'])
            ->assertForbidden();
    }

    public function test_creating_duplicate_role_fails_validation(): void
    {
        Role::findOrCreate('editor', 'sanctum');

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/users/roles', ['name' => 'Editor'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // PUT /dashboard/users/roles/{role}/permissions

    public function test_super_admin_can_assign_permissions_to_role(): void
    {
        $role = $this->webRole('instructor');

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/users/roles/{$role->id}/permissions", [
                'permissions' => ['course.view', 'course.update'],
            ])
            ->assertRedirect();

        $this->assertTrue($role->fresh()->hasPermissionTo('course.view'));
        $this->assertTrue($role->fresh()->hasPermissionTo('course.update'));
    }

    public function test_instructor_cannot_assign_permissions_to_role(): void
    {
        $role = $this->webRole('student');

        $this->actingAs($this->instructor())
            ->put("/dashboard/users/roles/{$role->id}/permissions", [
                'permissions' => ['course.view'],
            ])
            ->assertForbidden();
    }

    public function test_assigning_a_nonexistent_permission_fails_validation(): void
    {
        $role = $this->webRole('instructor');

        $this->actingAs($this->superAdmin())
            ->putJson("/dashboard/users/roles/{$role->id}/permissions", [
                'permissions' => ['does.not.exist'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['permissions.0']);
    }

    // PUT /dashboard/users/roles/{role}/users

    public function test_super_admin_can_assign_users_to_role(): void
    {
        $role = $this->webRole('instructor');
        $user = User::factory()->create();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/users/roles/{$role->id}/users", [
                'users' => [$user->id],
            ])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasRole('instructor'));
    }

    public function test_instructor_cannot_assign_users_to_role(): void
    {
        $role = $this->webRole('student');
        $user = User::factory()->create();

        $this->actingAs($this->instructor())
            ->put("/dashboard/users/roles/{$role->id}/users", [
                'users' => [$user->id],
            ])
            ->assertForbidden();
    }

    // DELETE /dashboard/users/roles/{role}

    public function test_super_admin_can_delete_an_unused_role(): void
    {
        $role = Role::create(['name' => 'temp_role', 'guard_name' => 'web']);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/users/roles/{$role->id}")
            ->assertRedirect('/dashboard/users/roles');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_cannot_delete_a_role(): void
    {
        $role = Role::create(['name' => 'temp_role', 'guard_name' => 'web']);

        $this->actingAs($this->admin())
            ->delete("/dashboard/users/roles/{$role->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_super_admin_cannot_delete_the_super_admin_role(): void
    {
        $role = $this->webRole('super_admin');

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/users/roles/{$role->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_a_role_with_assigned_users(): void
    {
        $role = $this->webRole('instructor');
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/users/roles/{$role->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    // GET /dashboard/users/permissions

    public function test_super_admin_can_view_permissions_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/users/permissions')
            ->assertOk();
    }

    public function test_instructor_cannot_view_permissions_page(): void
    {
        $this->actingAs($this->instructor())
            ->get('/dashboard/users/permissions')
            ->assertForbidden();
    }

    // PUT /dashboard/users/{user}/permissions

    public function test_super_admin_can_assign_permissions_directly_to_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/users/{$user->id}/permissions", [
                'permissions' => ['course.view', 'enrollment.view'],
            ])
            ->assertRedirect();

        $freshUser = $user->fresh();
        $this->assertTrue($freshUser->hasDirectPermission('course.view'));
        $this->assertTrue($freshUser->hasDirectPermission('enrollment.view'));
    }

    public function test_instructor_cannot_assign_permissions_directly_to_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->instructor())
            ->put("/dashboard/users/{$user->id}/permissions", [
                'permissions' => ['course.view'],
            ])
            ->assertForbidden();
    }
}
