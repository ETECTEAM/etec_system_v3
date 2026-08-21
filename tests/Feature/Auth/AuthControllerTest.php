<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\Auth\Events\PendingUserRegistered;
use App\Modules\Auth\Services\OtpService;
use Database\Seeders\Core\AssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthControllerTest extends TestCase
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

    private function activeUser(string $role = 'instructor', string $password = 'password123'): User
    {
        $user = User::factory()->create([
            'password' => bcrypt($password),
            'status' => UserStatus::Active,
        ]);
        $user->assignRole($role);

        return $user;
    }

    // GET /login and GET /instructor-register

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_guest_can_view_instructor_register_page(): void
    {
        $this->get('/instructor-register')->assertOk();
    }

    // POST /login

    public function test_user_can_login_with_email_and_correct_password(): void
    {
        $user = $this->activeUser();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_can_login_with_username_instead_of_email(): void
    {
        $user = $this->activeUser();

        $this->post('/login', [
            'login' => $user->name,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $user = $this->activeUser();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_with_unknown_email_is_rejected(): void
    {
        $this->post('/login', [
            'login' => 'nobody@etec.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_with_non_etec_email_fails_validation(): void
    {
        $this->post('/login', [
            'login' => 'someone@gmail.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'status' => UserStatus::Inactive,
        ]);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    // POST /logout

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_guest_cannot_access_logout(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }

    // POST /instructor-register

    public function test_guest_can_register_as_pending_instructor(): void
    {
        Event::fake([PendingUserRegistered::class]);

        $response = $this->post('/instructor-register', [
            'name' => 'New Instructor',
            'email' => 'new.instructor@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/code-verify');

        $user = User::where('email', 'new.instructor@etec.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertTrue($user->hasRole('instructor'));
        $this->assertStringStartsWith('ETEC-', $user->instructorData->instructor_code);
        $this->assertDatabaseHas('otp_verifications', ['user_id' => $user->id, 'verified_at' => null]);
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'user.registered']);

        Event::assertDispatched(PendingUserRegistered::class);
    }

    public function test_register_requires_an_etec_email(): void
    {
        $this->postJson('/instructor-register', [
            'name' => 'New Instructor',
            'email' => 'new.instructor@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        $existing = User::factory()->create();

        $this->postJson('/instructor-register', [
            'name' => 'New Instructor',
            'email' => $existing->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_matching_password_confirmation(): void
    {
        $this->postJson('/instructor-register', [
            'name' => 'New Instructor',
            'email' => 'new.instructor@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_register_activates_user_immediately_when_otp_is_disabled(): void
    {
        config(['auth.otp.enabled' => false]);

        $this->post('/instructor-register', [
            'name' => 'New Instructor',
            'email' => 'no.otp@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'no.otp@etec.com')->first();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertDatabaseMissing('otp_verifications', ['user_id' => $user->id]);
    }

    // GET /code-verify

    public function test_code_verify_page_redirects_to_register_without_a_pending_user(): void
    {
        $this->get('/code-verify')
            ->assertRedirect('/instructor-register');
    }

    public function test_code_verify_page_renders_for_a_pending_user(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Pending]);

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->get('/code-verify')
            ->assertOk();
    }

    public function test_code_verify_page_redirects_to_login_when_user_is_already_active(): void
    {
        $user = $this->activeUser();

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->get('/code-verify')
            ->assertRedirect('/login');
    }

    // POST /api/code-verify

    public function test_pending_user_can_verify_with_the_correct_code(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Pending]);
        $user->assignRole('instructor');
        [, $plainCode] = app(OtpService::class)->createForUser($user);

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->postJson('/api/code-verify', ['code' => $plainCode])
            ->assertOk()
            ->assertJsonPath('message', 'Account verified successfully.')
            ->assertJsonPath('redirect', '/dashboard');

        $fresh = $user->fresh();
        $this->assertSame(UserStatus::Active, $fresh->status);
        $this->assertAuthenticatedAs($user);
    }

    public function test_verification_fails_with_a_wrong_code(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Pending]);
        [$otp] = app(OtpService::class)->createForUser($user);

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->postJson('/api/code-verify', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertSame(UserStatus::Pending, $user->fresh()->status);
    }

    public function test_verification_fails_without_a_pending_session(): void
    {
        $this->postJson('/api/code-verify', ['code' => '123456'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_verification_reports_already_active_accounts(): void
    {
        $user = $this->activeUser();
        OtpVerification::create(['user_id' => $user->id, 'otp_code' => bcrypt('123456')]);

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->postJson('/api/code-verify', ['code' => '123456'])
            ->assertOk()
            ->assertJsonPath('message', 'Account is already active.');
    }
}
