<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    // Alerts are best-effort by design: a Telegram outage must never break a
    // (public, transactional) student registration, so every failure is logged
    // and swallowed here rather than propagated to callers.
    public function send(string $message): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if ($token === null || $token === '' || $chatId === null || $chatId === '') {
            return;
        }

        try {
            Http::asJson()
                ->timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
