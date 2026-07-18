<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Services\AuthService;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthService::class);
    }

    // findUserForLogin

    public function test_finds_a_user_by_email(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->service->findUserForLogin($user->email)->is($user));
    }

    public function test_finds_a_user_by_name_when_login_is_not_an_email(): void
    {
        $user = User::factory()->create(['name' => 'sokha']);

        $this->assertTrue($this->service->findUserForLogin('sokha')->is($user));
    }

    public function test_returns_null_for_an_empty_login(): void
    {
        User::factory()->create();

        $this->assertNull($this->service->findUserForLogin(''));
    }

    public function test_returns_null_for_an_unknown_login(): void
    {
        $this->assertNull($this->service->findUserForLogin('missing@etec.com'));
    }

    // ensureDefaultRole

    public function test_ensure_default_role_creates_the_role_when_missing(): void
    {
        $role = $this->service->ensureDefaultRole('editor', 'web');

        $this->assertDatabaseHas('roles', ['name' => 'editor', 'guard_name' => 'web']);
        $this->assertSame('editor', $role->name);
    }

    public function test_ensure_default_role_reuses_an_existing_role(): void
    {
        $existing = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $role = $this->service->ensureDefaultRole('editor', 'web');

        $this->assertTrue($role->is($existing));
    }

    // buildAuthPayload

    public function test_build_auth_payload_sanitizes_user_and_groups_permissions(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $user = User::factory()->create();
        $user->givePermissionTo(['course.view', 'course.update', 'enrollment.view']);

        $payload = $this->service->buildAuthPayload($user);

        $this->assertSame(
            ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            $payload['user']
        );
        $this->assertSame(['course.update', 'course.view'], $payload['permissions']['course']);
        $this->assertSame(['enrollment.view'], $payload['permissions']['enrollment']);
        $this->assertArrayNotHasKey('token', $payload);
        $this->assertArrayNotHasKey('message', $payload);
    }

    public function test_build_auth_payload_includes_token_and_message_when_given(): void
    {
        $user = User::factory()->create();

        $payload = $this->service->buildAuthPayload($user, 'secret-token', 'Welcome back');

        $this->assertSame('secret-token', $payload['token']);
        $this->assertSame('Welcome back', $payload['message']);
    }
}
