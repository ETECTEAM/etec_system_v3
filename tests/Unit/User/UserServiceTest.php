<?php

namespace Tests\Unit\User;

use App\Models\User;
use App\Modules\User\Data\StoreUserData;
use App\Modules\User\Data\UpdateUserData;
use App\Modules\User\Services\UserService;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->service = app(UserService::class);
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

    // assignableRolesFor

    public function test_assignable_roles_for_super_admin_includes_every_built_in_role(): void
    {
        $roles = $this->service->assignableRolesFor($this->superAdmin());

        $this->assertSame(['super_admin', 'admin', 'instructor', 'student'], $roles);
    }

    public function test_assignable_roles_for_admin_is_limited_to_operational_roles(): void
    {
        $roles = $this->service->assignableRolesFor($this->admin());

        $this->assertSame(['instructor', 'student'], $roles);
    }

    public function test_assignable_roles_for_unprivileged_user_is_empty(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->assertSame([], $this->service->assignableRolesFor($user));
    }

    // roleOptions

    public function test_role_options_returns_only_assignable_roles_under_the_web_guard(): void
    {
        $options = $this->service->roleOptions($this->admin());

        $this->assertSame(['instructor', 'student'], $options->all());
    }

    // queryVisibleUsers

    public function test_query_visible_users_returns_everyone_for_super_admin(): void
    {
        User::factory()->count(2)->create();

        $count = $this->service->queryVisibleUsers($this->superAdmin())->count();

        // 2 factory users + the super admin actor itself.
        $this->assertSame(3, $count);
    }

    public function test_query_visible_users_scopes_admin_to_instructors_and_students(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $visibleIds = $this->service->queryVisibleUsers($this->admin())->pluck('id')->all();

        $this->assertContains($instructor->id, $visibleIds);
        $this->assertNotContains($otherAdmin->id, $visibleIds);
    }

    public function test_query_visible_users_returns_none_for_unprivileged_user(): void
    {
        User::factory()->count(2)->create();
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $this->assertSame(0, $this->service->queryVisibleUsers($user)->count());
    }

    // create

    public function test_create_persists_user_assigns_role_and_builds_student_profile(): void
    {
        $data = new StoreUserData(
            name: 'New Student',
            email: 'student@etec.com',
            password: 'password123',
            role: 'student',
            status: 'active',
            avatar: null,
            student: [
                'full_name' => 'New Student',
                'gender' => 'male',
                'phone' => '012345678',
                'address' => null,
                'date_of_birth' => null,
                'status' => 'active',
            ],
            instructorData: [],
        );

        $user = $this->service->create($data);

        $this->assertTrue($user->hasRole('student'));
        $this->assertSame('New Student', $user->student->full_name);
        $this->assertNull($user->instructorData);
    }

    public function test_create_generates_an_instructor_code_when_none_is_supplied(): void
    {
        $data = new StoreUserData(
            name: 'New Instructor',
            email: 'instructor@etec.com',
            password: 'password123',
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: [
                'instructor_code' => null,
                'full_name' => 'New Instructor',
                'phone' => '012345678',
            ],
        );

        $user = $this->service->create($data);

        $this->assertTrue($user->hasRole('instructor'));
        $this->assertNotEmpty($user->instructorData->instructor_code);
        $this->assertStringStartsWith('ETEC-', $user->instructorData->instructor_code);
    }

    // update

    public function test_update_switches_profile_when_role_changes_from_student_to_instructor(): void
    {
        $created = $this->service->create(new StoreUserData(
            name: 'Switching User',
            email: 'switch@etec.com',
            password: 'password123',
            role: 'student',
            status: 'active',
            avatar: null,
            student: ['full_name' => 'Switching User', 'gender' => 'male', 'phone' => '012345678'],
            instructorData: [],
        ));

        $this->assertNotNull($created->student);

        $updated = $this->service->update($created, new UpdateUserData(
            name: 'Switching User',
            email: 'switch@etec.com',
            password: null,
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: ['full_name' => 'Switching User', 'phone' => '012345678'],
        ));

        $this->assertTrue($updated->hasRole('instructor'));
        $this->assertFalse($updated->hasRole('student'));
        $this->assertNull($updated->fresh(['student'])->student);
        $this->assertNotNull($updated->instructorData);
    }

    public function test_update_leaves_password_unchanged_when_left_blank(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $user->assignRole('instructor');

        $this->service->update($user, new UpdateUserData(
            name: $user->name,
            email: $user->email,
            password: null,
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: [],
        ));

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    // delete

    public function test_delete_removes_the_user(): void
    {
        $user = User::factory()->create();

        $this->service->delete($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    // ensureRoleIsAssignable

    public function test_ensure_role_is_assignable_throws_for_a_disallowed_role(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->ensureRoleIsAssignable($this->admin(), 'super_admin');
    }

    public function test_ensure_role_is_assignable_passes_for_an_allowed_role(): void
    {
        $this->service->ensureRoleIsAssignable($this->admin(), 'instructor');

        $this->addToAssertionCount(1);
    }

    // presentUser

    public function test_present_user_prefers_student_full_name_over_account_name(): void
    {
        $user = $this->service->create(new StoreUserData(
            name: 'Account Name',
            email: 'present.student@etec.com',
            password: 'password123',
            role: 'student',
            status: 'active',
            avatar: null,
            student: ['full_name' => 'Student Display Name', 'gender' => 'male', 'phone' => '012345678'],
            instructorData: [],
        ));

        $presented = $this->service->presentUser($user->fresh(['student', 'instructorData']));

        $this->assertSame('Student Display Name', $presented['name']);
    }

    // avatar / photo

    public function test_create_stores_an_uploaded_avatar_as_a_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $avatar = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');

        $user = $this->service->create(new StoreUserData(
            name: 'Photo User',
            email: 'photo.user@etec.com',
            password: 'password123',
            role: 'instructor',
            status: 'active',
            avatar: $avatar,
            student: [],
            instructorData: ['full_name' => 'Photo User'],
        ));

        $photo = $user->fresh(['photo'])->photo;

        $this->assertNotNull($photo);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($photo->file_path);
    }

    public function test_update_replaces_the_existing_photo_and_deletes_the_old_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = $this->service->create(new StoreUserData(
            name: 'Photo User',
            email: 'photo.replace@etec.com',
            password: 'password123',
            role: 'instructor',
            status: 'active',
            avatar: \Illuminate\Http\UploadedFile::fake()->image('first.jpg'),
            student: [],
            instructorData: ['full_name' => 'Photo User'],
        ));

        $originalPath = $user->fresh(['photo'])->photo->file_path;

        $this->service->update($user, new UpdateUserData(
            name: 'Photo User',
            email: 'photo.replace@etec.com',
            password: null,
            role: 'instructor',
            status: 'active',
            avatar: \Illuminate\Http\UploadedFile::fake()->image('second.jpg'),
            student: [],
            instructorData: [],
        ));

        $updatedPhoto = $user->fresh(['photo'])->photo;

        $this->assertNotSame($originalPath, $updatedPhoto->file_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($originalPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($updatedPhoto->file_path);
    }

    public function test_update_without_a_new_avatar_leaves_the_existing_photo_untouched(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = $this->service->create(new StoreUserData(
            name: 'Photo User',
            email: 'photo.keep@etec.com',
            password: 'password123',
            role: 'instructor',
            status: 'active',
            avatar: \Illuminate\Http\UploadedFile::fake()->image('keep.jpg'),
            student: [],
            instructorData: ['full_name' => 'Photo User'],
        ));

        $originalPath = $user->fresh(['photo'])->photo->file_path;

        $this->service->update($user, new UpdateUserData(
            name: 'Photo User Renamed',
            email: 'photo.keep@etec.com',
            password: null,
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: [],
        ));

        $this->assertSame($originalPath, $user->fresh(['photo'])->photo->file_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($originalPath);
    }
}
