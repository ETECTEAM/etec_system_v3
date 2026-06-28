<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
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

    public function test_guest_cannot_access_courses_endpoint(): void
    {
        $this->getJson('/api/courses')->assertUnauthorized();
    }

    public function test_user_with_course_view_permission_can_access_courses_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('message', 'Course list endpoint.');
    }

    public function test_user_without_course_view_permission_cannot_access_courses_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/courses')
            ->assertForbidden();
    }

    public function test_user_with_course_view_permission_can_access_courses_hi_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/courses/hi')
            ->assertOk()
            ->assertJsonPath('message', 'Course list endpoint.');
    }
}
