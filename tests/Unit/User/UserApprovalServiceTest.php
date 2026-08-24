<?php

namespace Tests\Unit\User;

use App\Enums\UserStatus;
use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\User\Services\UserApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserApprovalService::class);
    }

    public function test_approve_activates_the_user_and_stamps_verification_timestamps(): void
    {
        $user = User::factory()->unverified()->create([
            'status' => UserStatus::Pending,
        ]);

        $approved = $this->service->approve($user);

        $this->assertSame(UserStatus::Active, $approved->status);
        $this->assertNotNull($approved->verified_at);
        $this->assertNotNull($approved->email_verified_at);
    }

    public function test_approve_preserves_an_existing_verified_at_timestamp(): void
    {
        $existingTimestamp = now()->subDays(3);
        $user = User::factory()->create([
            'status' => UserStatus::Pending,
            'verified_at' => $existingTimestamp,
        ]);

        $approved = $this->service->approve($user);

        $this->assertSame($existingTimestamp->toDateTimeString(), $approved->verified_at->toDateTimeString());
    }

    public function test_approve_marks_the_latest_pending_otp_as_verified(): void
    {
        $user = User::factory()->create();

        $olderOtp = OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => '111111',
        ]);
        $latestOtp = OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => '222222',
        ]);

        $this->service->approve($user);

        $this->assertNotNull($latestOtp->fresh()->verified_at);
        $this->assertNull($olderOtp->fresh()->verified_at);
    }

    public function test_approve_does_not_touch_an_already_verified_otp(): void
    {
        $user = User::factory()->create();
        $verifiedOtp = OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => '333333',
            'verified_at' => $verifiedTimestamp = now()->subHour(),
        ]);

        $this->service->approve($user);

        $this->assertSame($verifiedTimestamp->toDateTimeString(), $verifiedOtp->fresh()->verified_at->toDateTimeString());
    }

    public function test_approve_logs_an_audit_action(): void
    {
        $user = User::factory()->create();

        $this->service->approve($user, actorId: null, source: 'telegram');

        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'action' => 'user.approved',
        ]);
    }

    public function test_reject_deletes_the_user_and_the_instructor_profile(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Pending,
        ]);
        $user->instructorData()->create([
            'full_name' => 'Pending Instructor',
            'instructor_code' => 'ETEC-9999',
        ]);

        $rejected = $this->service->reject($user);

        $this->assertSame($user->id, $rejected->id);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('instructor_data', ['user_id' => $user->id]);
    }

    public function test_reject_logs_an_audit_action(): void
    {
        $user = User::factory()->create();

        $this->service->reject($user);

        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'action' => 'user.rejected',
        ]);
    }
}
