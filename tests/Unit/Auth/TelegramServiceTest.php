<?php

namespace Tests\Unit\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\Auth\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\Laravel\Facades\Telegram;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The container exports CACHE_STORE=file, which outlives the test run
        // and would leak the one-day dedupe lock between runs. Isolate on the
        // per-process array store instead.
        config(['cache.default' => 'array']);
    }

    // BotsManager is final, so swap the facade root with a plain mock.
    private function swapTelegramFacade(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock();
        Telegram::swap($mock);

        return $mock;
    }

    private function pendingOtp(User $user): OtpVerification
    {
        return OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => bcrypt('123456'),
        ]);
    }

    public function test_nothing_is_sent_when_the_admin_chat_id_is_missing(): void
    {
        config(['telegram.admin_chat_id' => null]);
        $this->swapTelegramFacade()->shouldReceive('sendMessage')->never();

        $user = User::factory()->create();

        app(TelegramService::class)->sendAdminApprovalRequest($user, $this->pendingOtp($user), '123456');
    }

    public function test_approval_request_is_sent_to_the_admin_chat(): void
    {
        config([
            'telegram.admin_chat_id' => '12345',
            'telegram.default' => 'mybot',
            'telegram.bots.mybot.token' => 'test-token',
        ]);

        $this->swapTelegramFacade()->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (array $args): bool => $args['chat_id'] === '12345'
                && str_contains($args['text'], 'OTP: 123456'));

        $user = User::factory()->create();

        app(TelegramService::class)->sendAdminApprovalRequest($user, $this->pendingOtp($user), '123456');
    }

    public function test_duplicate_requests_for_the_same_otp_are_locked_out(): void
    {
        config([
            'telegram.admin_chat_id' => '12345',
            'telegram.default' => 'mybot',
            'telegram.bots.mybot.token' => 'test-token',
        ]);

        $this->swapTelegramFacade()->shouldReceive('sendMessage')->once();

        $user = User::factory()->create();
        $otp = $this->pendingOtp($user);
        $service = app(TelegramService::class);

        $service->sendAdminApprovalRequest($user, $otp, '123456');
        $service->sendAdminApprovalRequest($user, $otp, '123456');
    }
}
