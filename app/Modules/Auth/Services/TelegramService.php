<?php

namespace App\Modules\Auth\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends Telegram approval messages for pending registrations.
 */
class TelegramService
{
    public function sendAdminApprovalRequest(User $user, OtpVerification $otp, string $plainCode): void
    {
        $chatId = (string) config('services.telegram.otp_chat_id');
        $botToken = (string) config('services.telegram.otp_bot_token');
        $lockKey = "telegram:approval-request:{$otp->id}";

        if ($chatId === '') {
            Log::warning('Telegram OTP approval not sent: TELEGRAM_OTP_CHAT_ID is missing.', [
                'otp_id' => $otp->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        if ($botToken === '') {
            Log::warning('Telegram OTP approval not sent: TELEGRAM_OTP_BOT_TOKEN is missing.', [
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
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => $plainCode, 'copy_text' => ['text' => $plainCode]],
                            ],
                        ],
                    ]),
                ]);

            if (! $response->successful()) {
                Cache::forget($lockKey);

                Log::warning('Telegram OTP approval failed.', [
                    'otp_id' => $otp->id,
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Cache::forget($lockKey);

            Log::error('Telegram OTP approval failed.', [
                'otp_id' => $otp->id,
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);
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
            'Tap the OTP button below to copy the code.',
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
