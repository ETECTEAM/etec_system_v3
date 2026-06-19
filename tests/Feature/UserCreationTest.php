<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_super_admin_can_create_user()
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'student', 'guard_name' => 'sanctum']);

        $actor = User::factory()->create();
        $actor->assignRole('super_admin');

        $response = $this->actingAs($actor)
            ->post('/dashboard/users', [
                'name' => 'New User',
                'email' => 'new.user@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'student',
            ]);

        $response->assertRedirect('/dashboard/users');
        $this->assertDatabaseHas('users', ['email' => 'new.user@etec.com']);

        $user = User::where('email', 'new.user@etec.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('student'));
    }

    public function test_admin_cannot_assign_super_admin_role()
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        $actor = User::factory()->create();
        $actor->assignRole('admin');

        $response = $this->actingAs($actor)
            ->postJson('/dashboard/users', [
                'name' => 'Bad User',
                'email' => 'bad.user@etec.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'super_admin',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'bad.user@etec.com']);
    }
}
