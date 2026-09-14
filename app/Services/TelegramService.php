<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central Telegram client for OTP and application error notifications.
 */
class TelegramService
{
    /**
     * Send OTP notification.
     */
    public function sendOtpLog(string $message): void
    {
        $this->sendMessage(
            (string) config('services.telegram.otp_bot_token'),
            (string) config('services.telegram.otp_chat_id'),
            $message,
        );
    }

    /**
     * Send application error notification.
     *
     * Sends to:
     * 1. Error channel/chat
     * 2. Personal developer chat if configured
     */
    public function sendErrorLog(string $message): void
    {
        $botToken = trim(
            (string) config('services.telegram.error_bot_token')
        );

        $errorChatId = trim(
            (string) config('services.telegram.error_chat_id')
        );

        $personalChatId = trim(
            (string) config('services.telegram.personal_chat_id')
        );

        /*
        |--------------------------------------------------------------------------
        | Send to error channel
        |--------------------------------------------------------------------------
        */
        if ($errorChatId !== '') {
            $this->sendLongMessage(
                $botToken,
                $errorChatId,
                $message,
                'HTML'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Send to personal developer chat
        |--------------------------------------------------------------------------
        */
        if (
            $personalChatId !== '' &&
            $personalChatId !== $errorChatId
        ) {
            $this->sendLongMessage(
                $botToken,
                $personalChatId,
                $message,
                'HTML'
            );
        }
    }

    /**
     * Build a clean Telegram error message.
     */
    public function buildErrorMessage(
        Throwable $e,
        ?Request $request = null,
        ?string $command = null,
    ): string {
        $lines = [
            $this->errorTitle($e),

            '━━━━━━━━━━━━━━━━━━━━',

            '',
            '📱 APPLICATION',
            '• Name: ' . $this->telegramEscape((string) config('app.name')),
            '• Environment: ' . $this->telegramEscape((string) config('app.env')),
            '• Time: ' . now()->format('Y-m-d H:i:s'),

            '',
            $this->telegramCodeBlock(
                "❌ ERROR\n" .
                'Type: ' . $e::class . "\n" .
                'Message:' . "\n" .
                $this->fullExceptionMessage($e) . "\n\n" .
                "📍 LOCATION\n" .
                'File: ' . $e->getFile() . "\n" .
                'Line: ' . $e->getLine()
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | HTTP Request
        |--------------------------------------------------------------------------
        */
        if ($request) {
            $lines[] = '';
            $lines[] = '🌐 REQUEST';

            $lines[] = '• Method: ' .
                $this->telegramEscape(strtoupper($request->method()));

            $lines[] = '• Path: ' .
                $this->telegramEscape($request->path());

            $lines[] = '• URL: ' .
                $this->telegramEscape($request->fullUrl());

            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */
            if ($request->user()) {
                $lines[] = '';

                $lines[] = '👤 USER';

                $lines[] = '• ID: ' .
                    $this->telegramEscape((string) $request->user()->getAuthIdentifier());

                if (
                    isset($request->user()->name) &&
                    $request->user()->name !== ''
                ) {
                    $lines[] = '• Name: ' .
                        $this->telegramEscape((string) $request->user()->name);
                }

                if (
                    isset($request->user()->email) &&
                    $request->user()->email !== ''
                ) {
                    $lines[] = '• Email: ' .
                        $this->telegramEscape((string) $request->user()->email);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Artisan Command
        |--------------------------------------------------------------------------
        */
        elseif ($command) {
            $lines[] = '';

            $lines[] = '💻 COMMAND';

            $lines[] = '• ' . $this->telegramEscape($command);
        }

        /*
        |--------------------------------------------------------------------------
        | Footer
        |--------------------------------------------------------------------------
        */
        $lines[] = '';

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';

        $lines[] = '🔧 Please check the error above.@kungnorasmey';

        return implode("\n", $lines);
    }

    /**
     * Send a Telegram message.
     */
    public function sendMessage(
        string $botToken,
        string $chatId,
        string $message,
        ?string $parseMode = null
    ): void {
        $chatId = trim($chatId);
        $botToken = trim($botToken);

        if ($chatId === '' || $botToken === '') {
            return;
        }

        $url =
            "https://api.telegram.org/bot{$botToken}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning(
                    'Telegram message could not be sent.',
                    [
                        'chat_id' => $chatId,
                        'bot' => $this->botLabel($botToken),
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]
                );
            }
        } catch (Throwable $e) {
            Log::warning(
                'Telegram message could not be sent.',
                [
                    'chat_id' => $chatId,
                    'bot' => $this->botLabel($botToken),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Send long Telegram messages in multiple parts.
     *
     * Telegram has a maximum message length.
     * This prevents long error messages from being silently cut.
     */
    private function sendLongMessage(
        string $botToken,
        string $chatId,
        string $message,
        ?string $parseMode = null
    ): void {
        $message = trim($message);

        if ($message === '') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Safe Telegram message limit
        |--------------------------------------------------------------------------
        */
        $limit = 3900;

        $lines = explode(
            "\n",
            $message
        );

        $chunks = [];

        $currentChunk = '';

        foreach ($lines as $line) {
            /*
            |--------------------------------------------------------------------------
            | Extremely long single line
            |--------------------------------------------------------------------------
            */
            if (mb_strlen($line) > $limit) {
                if ($currentChunk !== '') {
                    $chunks[] = $currentChunk;
                    $currentChunk = '';
                }

                while (mb_strlen($line) > $limit) {
                    $chunks[] = mb_substr(
                        $line,
                        0,
                        $limit
                    );

                    $line = mb_substr(
                        $line,
                        $limit
                    );
                }

                if ($line !== '') {
                    $currentChunk = $line;
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Add line to current chunk
            |--------------------------------------------------------------------------
            */
            $newChunk = $currentChunk === ''
                ? $line
                : $currentChunk . "\n" . $line;

            /*
            |--------------------------------------------------------------------------
            | Start another chunk if needed
            |--------------------------------------------------------------------------
            */
            if (mb_strlen($newChunk) > $limit) {
                if ($currentChunk !== '') {
                    $chunks[] = $currentChunk;
                }

                $currentChunk = $line;
            } else {
                $currentChunk = $newChunk;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final chunk
        |--------------------------------------------------------------------------
        */
        if ($currentChunk !== '') {
            $chunks[] = $currentChunk;
        }

        /*
        |--------------------------------------------------------------------------
        | Send chunks
        |--------------------------------------------------------------------------
        */
        $total = count($chunks);

        foreach ($chunks as $index => $chunk) {
            if ($total > 1) {
                $chunk =
                    "📦 PART " .
                    ($index + 1) .
                    "/" .
                    $total .
                    "\n\n" .
                    $chunk;
            }

            $this->sendMessage(
                $botToken,
                $chatId,
                $chunk,
                $total === 1 ? $parseMode : null
            );
        }
    }


    /**
     * Hide most of the Telegram bot token in local logs.
     */
    private function botLabel(
        string $botToken
    ): string {
        $prefix = mb_substr(
            $botToken,
            0,
            6
        );

        return $prefix === ''
            ? 'unknown'
            : $prefix . '***';
    }

    /**
     * Escape dynamic text before sending Telegram HTML messages.
     */
    private function telegramEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Make exact error details easier to copy in Telegram.
     */
    private function telegramCodeBlock(string $value): string
    {
        return '<pre><code>' . $this->telegramEscape(
            $this->wrapTelegramCodeText($value)
        ) . '</code></pre>';
    }

    /**
     * Keep Telegram code blocks readable on desktop and mobile.
     */
    private function wrapTelegramCodeText(string $value): string
    {
        return collect(explode("\n", $value))
            ->map(fn (string $line): string => wordwrap($line, 88, "\n", true))
            ->implode("\n");
    }

    /**
     * Detect error category.
     */
    private function errorTitle(
        Throwable $e
    ): string {
        return match (true) {
            str_contains(
                $e::class,
                'Validation'
            )
                => '⚠️ VALIDATION ERROR',

            str_contains(
                $e::class,
                'Authentication'
            )
                => '🔐 AUTHENTICATION ERROR',

            str_contains(
                $e::class,
                'Authorization'
            )
                => '🚫 AUTHORIZATION ERROR',

            str_contains(
                $e::class,
                'Query'
            ) ||
            str_contains(
                $e::class,
                'Database'
            )
                => '🛢️ DATABASE ERROR',

            str_contains(
                $e::class,
                'Telegram'
            )
                => '✈️ TELEGRAM ERROR',

            str_contains(
                $e::class,
                'Http'
            ) ||
            str_contains(
                $e::class,
                'Request'
            )
                => '🌐 HTTP REQUEST ERROR',

            str_contains(
                $e::class,
                'File'
            )
                => '📁 FILE ERROR',

            default
                => '🚨 APPLICATION ERROR',
        };
    }

    /**
     * Get full exception message.
     */
    private function fullExceptionMessage(
        Throwable $e
    ): string {
        $message = trim(
            $e->getMessage()
        );

        if ($message === '') {
            return 'No message provided.';
        }

        return $message;
    }
}
