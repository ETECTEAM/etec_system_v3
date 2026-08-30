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
use Illuminate\Support\Carbon;
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

    // Access expiry (instructor first-login clock)

    public function test_instructor_first_login_starts_the_access_clock(): void
    {
        $user = $this->activeUser('instructor');
        $this->assertNull($user->fresh()->access_expires_at);
        $this->assertNull($user->fresh()->access_renewed_at);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->access_expires_at);
        $this->assertTrue($fresh->access_expires_at->isSameDay(now()->addMonth()));
        $this->assertNotNull($fresh->access_renewed_at);
        $this->assertTrue($fresh->access_renewed_at->isToday());
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'login.window_started']);
    }

    public function test_instructor_login_restarts_the_expiry_from_this_login(): void
    {
        $user = $this->activeUser('instructor');
        $user->forceFill(['access_expires_at' => now()->addDays(20)])->save();

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $fresh = $user->fresh();
        $this->assertTrue($fresh->access_expires_at->isSameDay(now()->addMonth()));
        $this->assertTrue($fresh->access_renewed_at->isToday());
    }

    public function test_instructor_can_login_before_expiry(): void
    {
        $user = $this->activeUser('instructor');
        $user->forceFill(['access_expires_at' => now()->addDay()])->save();

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_instructor_login_renews_access_and_preserves_data(): void
    {
        $user = $this->activeUser('instructor');
        // Renewed over a month ago => derived expiry (renewed_at + 1 month) is in the past.
        $user->forceFill([
            'access_expires_at' => now()->subMonth(),
            'access_renewed_at' => now()->subMonths(2),
        ])->save();

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $fresh = $user->fresh();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($fresh->access_renewed_at->isToday());
        $this->assertTrue($fresh->access_renewed_at->copy()->addMonth()->isSameDay(now()->addMonth()));
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'login.access_renewed']);
    }

    public function test_expired_instructor_login_renews_even_exactly_at_expiry(): void
    {
        $user = $this->activeUser('instructor');
        $user->forceFill(['access_expires_at' => now()])->save();

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->access_expires_at->isSameDay(now()->addMonth()));
    }

    public function test_admin_and_super_admin_login_gets_one_year_expiry(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $user = $this->activeUser($role);

            $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
                ->assertRedirect('/dashboard');

            $fresh = $user->fresh();
            $this->assertNotNull($fresh->access_expires_at);
            $this->assertTrue($fresh->access_expires_at->isSameDay(now()->addYear()));
            $this->assertTrue($fresh->access_renewed_at->isToday());

            $this->post('/logout');
        }
    }

    public function test_expired_instructor_is_signed_out_and_sent_to_login_on_next_request(): void
    {
        $user = $this->activeUser('instructor');
        $user->forceFill(['access_expires_at' => now()->subDay()])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'login.access_expired']);
    }

    public function test_instructor_with_inverted_window_is_signed_out_on_next_request(): void
    {
        $user = $this->activeUser('instructor');
        // Deadline is NOT strictly ahead of the last renewal (deadline <= renewed_at).
        $user->forceFill([
            'access_renewed_at' => now(),
            'access_expires_at' => now()->addDay()->subDay(), // same moment, <= renewed_at
        ])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    // Role-based token expiration

    public function test_admin_login_gets_one_year_expiry(): void
    {
        $user = $this->activeUser('admin');

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->access_expires_at);
        $this->assertTrue($fresh->access_expires_at->isSameDay(now()->addYear()));
        $this->assertTrue($fresh->access_renewed_at->isToday());
    }

    public function test_super_admin_login_gets_one_year_expiry(): void
    {
        $user = $this->activeUser('super_admin');

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->access_expires_at);
        $this->assertTrue($fresh->access_expires_at->isSameDay(now()->addYear()));
        $this->assertTrue($fresh->access_renewed_at->isToday());
    }

    public function test_expired_admin_is_signed_out_and_json_requests_get_401(): void
    {
        $user = $this->activeUser('admin');
        $user->forceFill(['access_expires_at' => now()->subDay()])->save();

        $this->actingAs($user)
            ->getJson('/dashboard')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Your access has expired. Please log in again to renew your access.');

        $this->assertGuest();
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'login.access_expired']);
    }

    public function test_expiration_is_an_exact_offset_from_the_login_issued_time(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');

        $instructor = $this->activeUser('instructor');
        $this->post('/login', ['login' => $instructor->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $this->assertTrue($instructor->fresh()->access_expires_at->eq(Carbon::parse('2026-09-29 10:00:00')));

        $this->post('/logout');

        $admin = $this->activeUser('admin');
        $this->post('/login', ['login' => $admin->email, 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $this->assertTrue($admin->fresh()->access_expires_at->eq(Carbon::parse('2027-08-29 10:00:00')));

        Carbon::setTestNow();
    }

    public function test_expiration_role_comes_from_the_backend_not_the_request(): void
    {
        $admin = $this->activeUser('admin');

        // A forged role field in the login payload must not shorten the token.
        $this->post('/login', [
            'login' => $admin->email,
            'password' => 'password123',
            'role' => 'instructor',
        ])->assertRedirect('/dashboard');

        $this->assertTrue($admin->fresh()->access_expires_at->isSameDay(now()->addYear()));
    }

    public function test_otp_activation_login_mints_the_role_expiry(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Pending]);
        $user->assignRole('instructor');
        [, $plainCode] = app(OtpService::class)->createForUser($user);

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->postJson('/api/code-verify', ['code' => $plainCode])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->access_expires_at);
        $this->assertTrue($fresh->access_expires_at->isSameDay(now()->addMonth()));
        $this->assertTrue($fresh->access_renewed_at->isToday());
    }

    public function test_otp_disabled_registration_login_mints_the_role_expiry(): void
    {
        config(['auth.otp.enabled' => false]);

        $this->post('/instructor-register', [
            'name' => 'OTP Disabled Instructor',
            'email' => 'otp.off@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'otp.off@etec.com')->first();
        $this->assertTrue($user->fresh()->access_expires_at->isSameDay(now()->addMonth()));
        $this->assertTrue($user->fresh()->access_renewed_at->isToday());
    }

    public function test_other_roles_keep_the_existing_never_expire_behavior(): void
    {
        $user = $this->activeUser('student');

        $this->post('/login', ['login' => $user->email, 'password' => 'password123'])
            ->assertStatus(302);

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->access_expires_at);
        $this->assertNull($user->fresh()->access_renewed_at);
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
        $this->assertGuest();

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

    public function test_newly_verified_instructor_with_incomplete_setup_is_sent_to_profile(): void
    {
        // A self-registered instructor (requires_onboarding) who has not yet
        // filled in employment type / work schedule / specialization or verified
        // a recovery email is held out of the dashboard after OTP verification.
        $user = User::factory()->create([
            'status' => UserStatus::Pending,
            'requires_onboarding' => true,
        ]);
        $user->assignRole('instructor');
        [, $plainCode] = app(OtpService::class)->createForUser($user);

        // Include the user_id so this uses its own rate-limiter bucket rather
        // than the shared one used by the other code-verify passing tests.
        $this->withSession(['pending_verification_user_id' => $user->id])
            ->postJson('/api/code-verify', ['code' => $plainCode, 'user_id' => $user->id])
            ->assertOk()
            ->assertJsonPath('message', 'Account verified successfully.')
            ->assertJsonPath('redirect', '/dashboard/instructor/profile');
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
            ->assertOk()
            ->assertJsonPath('message', 'Your registration was rejected. Please register again.')
            ->assertJsonPath('redirect', '/instructor-register');
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

    public function test_verification_redirects_rejected_users_back_to_registration(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Rejected]);
        $user->assignRole('instructor');
        OtpVerification::create(['user_id' => $user->id, 'otp_code' => bcrypt('123456')]);

        $this->withSession(['pending_verification_user_id' => $user->id])
            ->postJson('/api/code-verify', ['code' => '123456'])
            ->assertOk()
            ->assertJsonPath('message', 'Your registration was rejected. Please register again.')
            ->assertJsonPath('redirect', '/instructor-register');
    }
}
