<?php

namespace Tests\Feature\User;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    // Explicit status is required here: the users table only defaults
    // 'status' to 'active' at the DB level, and actingAs() hands the
    // EnsureAccountIsActive middleware this exact in-memory instance rather
    // than a fresh read - a factory-created model that never had 'status'
    // set keeps a null in-memory attribute even though the row itself
    // defaulted correctly, which the middleware reads as inactive.
    private function superAdmin(): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole('admin');

        return $user;
    }

    // GET /dashboard/users

    public function test_super_admin_can_view_users_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/users')
            ->assertOk();
    }

    public function test_admin_can_view_users_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/users')
            ->assertOk();
    }

    public function test_instructor_cannot_view_users_index(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole('instructor');

        $this->actingAs($user)
            ->get('/dashboard/users')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_users_index(): void
    {
        $this->get('/dashboard/users')
            ->assertRedirect('/login');
    }

    // GET /dashboard/users/data

    public function test_super_admin_can_view_paginated_user_data(): void
    {
        User::factory()->count(3)->create();

        $this->actingAs($this->superAdmin())
            ->getJson('/dashboard/users/data')
            ->assertOk()
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_instructor_cannot_view_paginated_user_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->actingAs($user)
            ->getJson('/dashboard/users/data')
            ->assertForbidden();
    }

    // GET /dashboard/users/create

    public function test_super_admin_can_view_create_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/users/create')
            ->assertOk();
    }

    public function test_instructor_cannot_view_create_page(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole('instructor');

        $this->actingAs($user)
            ->get('/dashboard/users/create')
            ->assertForbidden();
    }

    // POST /dashboard/users

    public function test_super_admin_can_create_an_instructor_user(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/dashboard/users', [
                'name' => 'New Instructor',
                'email' => 'new.instructor@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'instructor',
                'account_status' => 'active',
                'instructor_full_name' => 'New Instructor',
            ]);

        $response->assertRedirect('/dashboard/users');
        $this->assertDatabaseHas('users', ['email' => 'new.instructor@etec.com']);

        $user = User::where('email', 'new.instructor@etec.com')->first();
        $this->assertTrue($user->hasRole('instructor'));
        $this->assertNotNull($user->instructorData);
    }

    public function test_admin_cannot_assign_super_admin_role_when_creating_user(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson('/dashboard/users', [
                'name' => 'Bad User',
                'email' => 'bad.user@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'super_admin',
                'account_status' => 'active',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'bad.user@etec.com']);
    }

    public function test_create_requires_matching_password_confirmation(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/users', [
                'name' => 'New Instructor',
                'email' => 'mismatch@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'different123',
                'role' => 'instructor',
                'account_status' => 'active',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_instructor_cannot_create_users(): void
    {
        // Instructors have no assignable roles, so StoreUserRequest's
        // Rule::in([]) rejects every role choice before the create policy
        // ever runs — the observable failure is a validation error, not 403.
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole('instructor');

        $this->actingAs($user)
            ->postJson('/dashboard/users', [
                'name' => 'New Instructor',
                'email' => 'blocked@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'instructor',
                'account_status' => 'active',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'blocked@etec.com']);
    }

    // GET /dashboard/users/{user} and /dashboard/users/edit/{user}

    public function test_super_admin_can_view_any_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('admin');

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/users/{$target->id}")
            ->assertOk();
    }

    public function test_admin_can_view_instructor_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('instructor');

        $this->actingAs($this->admin())
            ->get("/dashboard/users/{$target->id}")
            ->assertOk();
    }

    public function test_admin_cannot_view_another_admin_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('admin');

        $this->actingAs($this->admin())
            ->get("/dashboard/users/{$target->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_edit_page_for_student(): void
    {
        $target = User::factory()->create();
        $target->assignRole('student');

        $this->actingAs($this->admin())
            ->get("/dashboard/users/edit/{$target->id}")
            ->assertOk();
    }

    // PUT /dashboard/users/{user}

    public function test_super_admin_can_update_a_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('instructor');

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/users/{$target->id}", [
                'name' => 'Updated Name',
                'email' => $target->email,
                'role' => 'instructor',
                'account_status' => 'active',
                'instructor_full_name' => 'Updated Name',
            ])
            ->assertRedirect('/dashboard/users');

        $this->assertSame('Updated Name', $target->fresh()->name);
    }

    public function test_admin_cannot_update_another_admin_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('admin');

        // Use a role the acting admin IS allowed to assign (instructor) so the
        // request passes validation and we actually exercise the "manage"
        // policy denial, rather than tripping the role Rule::in check first.
        $this->actingAs($this->admin())
            ->put("/dashboard/users/{$target->id}", [
                'name' => 'Hacked Name',
                'email' => $target->email,
                'role' => 'instructor',
                'account_status' => 'active',
            ])
            ->assertForbidden();
    }

    // DELETE /dashboard/users/{user}

    public function test_super_admin_can_delete_a_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('instructor');

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/users/{$target->id}")
            ->assertRedirect('/dashboard/users');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_another_admin_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('admin');

        $this->actingAs($this->admin())
            ->delete("/dashboard/users/{$target->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_super_admin_can_create_a_student_user(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/dashboard/users', [
                'name' => 'New Student',
                'email' => 'new.student@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'student',
                'account_status' => 'active',
                'student_full_name' => 'New Student',
                'student_gender' => 'male',
                'student_phone' => '012345678',
            ]);

        $response->assertRedirect('/dashboard/users');
        $this->assertDatabaseHas('users', ['email' => 'new.student@etec.com']);

        $user = User::where('email', 'new.student@etec.com')->first();
        $this->assertTrue($user->hasRole('student'));
        $this->assertSame('New Student', $user->student?->full_name);
    }
}
