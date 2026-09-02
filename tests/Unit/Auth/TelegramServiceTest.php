<?php

namespace Tests\Unit\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use App\Modules\Auth\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        // Real credentials so the send path is actually exercised; the base
        // TestCase already faked Http so nothing leaves the process.
        config([
            'services.telegram.otp_bot_token' => 'test-token',
            'services.telegram.otp_chat_id' => '12345',
        ]);

        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);
    }

    private function pendingOtp(User $user): OtpVerification
    {
        return OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => bcrypt('123456'),
        ]);
    }

    public function test_nothing_is_sent_when_the_otp_chat_id_is_missing(): void
    {
        config(['services.telegram.otp_chat_id' => null]);

        $user = User::factory()->create();

        app(TelegramService::class)->sendAdminApprovalRequest($user, $this->pendingOtp($user), '123456');

        Http::assertNothingSent();
    }

    public function test_nothing_is_sent_when_the_bot_token_is_missing(): void
    {
        config(['services.telegram.otp_bot_token' => null]);

        $user = User::factory()->create();

        app(TelegramService::class)->sendAdminApprovalRequest($user, $this->pendingOtp($user), '123456');

        Http::assertNothingSent();
    }

    public function test_approval_request_is_sent_to_the_admin_chat(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['email' => 'srinnalen@etec.com'])->save();

        app(TelegramService::class)->sendAdminApprovalRequest($user, $this->pendingOtp($user), '123456');

        Http::assertSent(function ($request): bool {
            $markup = json_decode($request['reply_markup'] ?? '{}', true);

            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '12345'
                && str_contains($request['text'], 'Email: sri***@etec.com')
                && str_contains($request['text'], 'OTP: 123456')
                && str_contains($request['text'], 'Tap the OTP button below to copy the code.')
                && ($markup['inline_keyboard'][0][0]['text'] ?? null) === '123456'
                && ($markup['inline_keyboard'][0][0]['copy_text']['text'] ?? null) === '123456';
        });
    }

    public function test_duplicate_requests_for_the_same_otp_are_locked_out(): void
    {
        $user = User::factory()->create();
        $otp = $this->pendingOtp($user);
        $service = app(TelegramService::class);

        $service->sendAdminApprovalRequest($user, $otp, '123456');
        $service->sendAdminApprovalRequest($user, $otp, '123456');

        Http::assertSentCount(1);
    }
}
