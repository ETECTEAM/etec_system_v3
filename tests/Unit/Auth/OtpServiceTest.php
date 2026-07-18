<?php

namespace Tests\Unit\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\Auth\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OtpService::class);
    }

    // createForUser

    public function test_create_stores_a_hashed_code_never_the_plain_one(): void
    {
        $user = User::factory()->create();

        [$otp, $plainCode] = $this->service->createForUser($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $plainCode);
        $this->assertNotSame($plainCode, $otp->otp_code);
        $this->assertTrue(Hash::check($plainCode, $otp->otp_code));
    }

    public function test_create_sets_a_ten_minute_expiry_and_logs_an_audit_entry(): void
    {
        $user = User::factory()->create();

        [$otp] = $this->service->createForUser($user);

        $this->assertTrue($otp->expires_at->isFuture());
        $this->assertTrue($otp->expires_at->lte(now()->addMinutes(10)));
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'otp.created']);
    }

    // verify

    public function test_verify_accepts_the_correct_code_and_marks_it_verified(): void
    {
        $user = User::factory()->create();
        [, $plainCode] = $this->service->createForUser($user);

        $verified = $this->service->verify($user, $plainCode);

        $this->assertNotNull($verified->verified_at);
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'otp.verified']);
    }

    public function test_verify_rejects_a_wrong_code_and_counts_the_attempt(): void
    {
        $user = User::factory()->create();
        [$otp] = $this->service->createForUser($user);

        try {
            $this->service->verify($user, '000000');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
        }

        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertNull($otp->fresh()->verified_at);
        $this->assertDatabaseHas('auth_audit_logs', ['user_id' => $user->id, 'action' => 'otp.failed']);
    }

    public function test_verify_rejects_an_expired_code(): void
    {
        $user = User::factory()->create();
        [$otp, $plainCode] = $this->service->createForUser($user);
        $otp->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->expectException(ValidationException::class);

        $this->service->verify($user, $plainCode);
    }

    public function test_verify_blocks_after_five_failed_attempts_even_with_the_correct_code(): void
    {
        $user = User::factory()->create();
        [$otp, $plainCode] = $this->service->createForUser($user);
        $otp->forceFill(['attempts' => 5])->save();

        $this->expectException(ValidationException::class);

        $this->service->verify($user, $plainCode);
    }

    public function test_verify_fails_when_the_user_has_no_pending_otp(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->verify($user, '123456');
    }

    public function test_verify_uses_the_latest_unverified_otp(): void
    {
        $user = User::factory()->create();
        [$oldOtp] = $this->service->createForUser($user);
        [, $latestCode] = $this->service->createForUser($user);

        $verified = $this->service->verify($user, $latestCode);

        $this->assertNotSame($oldOtp->id, $verified->id);
        $this->assertNull($oldOtp->fresh()->verified_at);
    }
}
