<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Services\TokenExpirationService;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenExpirationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenExpirationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->service = app(TokenExpirationService::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_instructor_token_expires_one_month_after_issuance(): void
    {
        $user = $this->userWithRole('instructor');

        $expiresAt = $this->service->expiresAt($user, '2026-08-29 10:00:00');

        $this->assertEquals('2026-09-29 10:00:00', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_admin_token_expires_one_year_after_issuance(): void
    {
        $user = $this->userWithRole('admin');

        $expiresAt = $this->service->expiresAt($user, '2026-08-29 10:00:00');

        $this->assertEquals('2027-08-29 10:00:00', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_super_admin_token_expires_one_year_after_issuance(): void
    {
        $user = $this->userWithRole('super_admin');

        $expiresAt = $this->service->expiresAt($user, '2026-08-29 10:00:00');

        $this->assertEquals('2027-08-29 10:00:00', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_role_without_configuration_never_expires(): void
    {
        $user = $this->userWithRole('student');

        $this->assertNull($this->service->durationFor($user));
        $this->assertNull($this->service->expiresAt($user, '2026-08-29 10:00:00'));
    }

    public function test_user_without_any_known_role_never_expires(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->roleFor($user));
        $this->assertNull($this->service->expiresAt($user));
    }

    public function test_most_privileged_role_wins(): void
    {
        $user = $this->userWithRole('instructor');
        $user->assignRole('super_admin');

        $duration = $this->service->durationFor($user);

        $this->assertSame('super_admin', $this->service->roleFor($user));
        $this->assertSame(1, $duration->y);
    }

    public function test_expiry_is_an_exact_offset_preserving_clock_time(): void
    {
        $user = $this->userWithRole('instructor');

        $expiresAt = $this->service->expiresAt($user, '2026-08-29 23:45:11');

        // Same clock time one calendar month later, not a calendar boundary
        // (matches the existing addMonth() semantics for month-end overflow).
        $this->assertEquals('2026-09-29 23:45:11', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_issued_at_defaults_to_now(): void
    {
        $user = $this->userWithRole('admin');

        $expiresAt = $this->service->expiresAt($user);

        $this->assertTrue($expiresAt->isSameDay(now()->addYear()));
    }

    public function test_configuration_is_centralized_and_env_drivable(): void
    {
        config(['auth.token_expiration.roles.instructor' => 'P7D']);

        $user = $this->userWithRole('instructor');

        $this->assertEquals(
            '2026-09-05 10:00:00',
            $this->service->expiresAt($user, '2026-08-29 10:00:00')->format('Y-m-d H:i:s')
        );
    }
}
