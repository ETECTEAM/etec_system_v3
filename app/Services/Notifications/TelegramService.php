<?php

namespace App\Services\Notifications;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class TelegramService
{
    public function sendAdminApprovalRequest(User $user, OtpVerification $otp, string $plainCode): void
    {
        $chatId = config('telegram.admin_chat_id');
        $lockKey = "telegram:approval-request:{$otp->id}";

        if (! $chatId || ! Cache::add($lockKey, true, now()->addDay())) {
            return;
        }

        $message = $this->buildMessage($user, $plainCode);

        try {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => 'Approve', 'callback_data' => "approve:{$otp->id}"],
                            ['text' => 'Reject', 'callback_data' => "reject:{$otp->id}"],
                        ],
                    ],
                ]),
            ]);
        } catch (Throwable $e) {
            Cache::forget($lockKey);

            throw $e;
        }
    }

    private function buildMessage(User $user, string $plainCode): string
    {
        $email = $user->email ?? 'n/a';

        return implode("\n", [
            'New Instructor Registration',
            '',
            'Name: '.$user->name,
            'Email: '.$email,
            'Phone: not provided',
            '',
            'OTP: '.$plainCode,
            '',
            'Please verify this instructor.',
        ]);
    }
}
