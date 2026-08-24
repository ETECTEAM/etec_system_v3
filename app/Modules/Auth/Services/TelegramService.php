<?php

namespace App\Modules\Auth\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

/**
 * Sends Telegram approval messages for pending registrations.
 */
class TelegramService
{
    public function sendAdminApprovalRequest(User $user, OtpVerification $otp, string $plainCode): void
    {
        $adminChatId = config('telegram.admin_chat_id');
        $botToken = config('telegram.bots.'.config('telegram.default', 'mybot').'.token');
        $lockKey = "telegram:approval-request:{$otp->id}";

        if (! $adminChatId) {
            Log::warning('Telegram OTP approval not sent: TELEGRAM_ADMIN_CHAT_ID is missing.', [
                'otp_id' => $otp->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        if (! $botToken) {
            Log::warning('Telegram OTP approval not sent: TELEGRAM_BOT_TOKEN is missing.', [
                'otp_id' => $otp->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        if (! Cache::add($lockKey, true, now()->addDay())) {
            return;
        }

        $message = $this->buildMessage($user, $plainCode);

        try {
            Telegram::sendMessage([
                'chat_id' => $adminChatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => 'Copy code', 'copy_text' => ['text' => $plainCode]],
                        ],
                        [
                            ['text' => 'Approve', 'callback_data' => "approve:{$otp->id}"],
                            ['text' => 'Reject', 'callback_data' => "reject:{$otp->id}"],
                        ],
                    ],
                ]),
            ]);
        } catch (Throwable $e) {
            Cache::forget($lockKey);

            Log::error('Telegram OTP approval failed.', [
                'otp_id' => $otp->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildMessage(User $user, string $plainCode): string
    {
        $email = $user->email ?? 'n/a';

        return implode("\n", [
            'New Instructor Registration',
            '',
            'Name: '.$this->escapeHtml($user->name),
            'Email: '.$this->escapeHtml($this->maskEmail($email)),
            '',
            'OTP: '.$plainCode,
            '',
            'Please verify this instructor.',
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return $email;
        }

        $visible = mb_substr($localPart, 0, 3);

        return "{$visible}***@{$domain}";
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
