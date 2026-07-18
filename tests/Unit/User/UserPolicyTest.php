<?php

namespace Tests\Unit\User;

use App\Models\User;
use App\Modules\User\Policies\UserPolicy;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->policy = new UserPolicy();
    }

    private function withRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    // viewAny

    public function test_super_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->withRole('super_admin')));
    }

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->withRole('admin')));
    }

    public function test_instructor_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny($this->withRole('instructor')));
    }

    public function test_student_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny($this->withRole('student')));
    }

    // create

    public function test_super_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->withRole('super_admin')));
    }

    public function test_instructor_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->withRole('instructor')));
    }

    // manage

    public function test_super_admin_can_manage_any_user(): void
    {
        $superAdmin = $this->withRole('super_admin');

        $this->assertTrue($this->policy->manage($superAdmin, $this->withRole('admin')));
        $this->assertTrue($this->policy->manage($superAdmin, $this->withRole('instructor')));
        $this->assertTrue($this->policy->manage($superAdmin, $this->withRole('student')));
    }

    public function test_admin_can_manage_instructors_and_students(): void
    {
        $admin = $this->withRole('admin');

        $this->assertTrue($this->policy->manage($admin, $this->withRole('instructor')));
        $this->assertTrue($this->policy->manage($admin, $this->withRole('student')));
    }

    public function test_admin_cannot_manage_another_admin_or_a_super_admin(): void
    {
        $admin = $this->withRole('admin');

        $this->assertFalse($this->policy->manage($admin, $this->withRole('admin')));
        $this->assertFalse($this->policy->manage($admin, $this->withRole('super_admin')));
    }

    public function test_unprivileged_user_cannot_manage_anyone(): void
    {
        $instructor = $this->withRole('instructor');

        $this->assertFalse($this->policy->manage($instructor, $this->withRole('student')));
    }

    // update / delete mirror manage()

    public function test_update_mirrors_manage_result(): void
    {
        $admin = $this->withRole('admin');
        $student = $this->withRole('student');
        $otherAdmin = $this->withRole('admin');

        $this->assertTrue($this->policy->update($admin, $student));
        $this->assertFalse($this->policy->update($admin, $otherAdmin));
    }

    public function test_delete_mirrors_manage_result(): void
    {
        $admin = $this->withRole('admin');
        $student = $this->withRole('student');
        $otherAdmin = $this->withRole('admin');

        $this->assertTrue($this->policy->delete($admin, $student));
        $this->assertFalse($this->policy->delete($admin, $otherAdmin));
    }
}
