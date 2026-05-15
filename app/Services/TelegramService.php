<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public function sendAdminApprovalRequest(User $user, OtpVerification $otp, string $plainCode): void
    {
        $chatId = config('telegram.admin_chat_id');

        if (! $chatId) {
            return;
        }

        $message = $this->buildMessage($user, $plainCode);

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
