<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\Permission\AssignPermissionSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Permission\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
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

    public function test_user_can_register_and_receive_token_with_default_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registered successfully.')
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonPath('roles.0', 'instructor')
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'roles',
                'permissions',
                'token',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'tester@example.com',
            'password' => 'password123',
        ]);
        $user->assignRole('instructor');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'tester@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logged in successfully.')
            ->assertJsonPath('user.email', 'tester@example.com')
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'roles',
                'permissions',
                'token',
                'message',
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'tester@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'tester@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('roles.0', 'instructor');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');
        $token = $user->createToken('api-token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
