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
                $message
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
                $message
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
            '• Name: ' . config('app.name'),
            '• Environment: ' . config('app.env'),
            '• Time: ' . now()->format('Y-m-d H:i:s'),

            '',
            '❌ ERROR',
            '• Type: ' . $e::class,
            '• Message:',
            $this->fullExceptionMessage($e),

            '',
            '📍 LOCATION',
            '• File: ' . $e->getFile(),
            '• Line: ' . $e->getLine(),
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
                strtoupper($request->method());

            $lines[] = '• Path: ' .
                $request->path();

            $lines[] = '• URL: ' .
                $request->fullUrl();

            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */
            if ($request->user()) {
                $lines[] = '';

                $lines[] = '👤 USER';

                $lines[] = '• ID: ' .
                    $request->user()->getAuthIdentifier();

                if (
                    isset($request->user()->name) &&
                    $request->user()->name !== ''
                ) {
                    $lines[] = '• Name: ' .
                        $request->user()->name;
                }

                if (
                    isset($request->user()->email) &&
                    $request->user()->email !== ''
                ) {
                    $lines[] = '• Email: ' .
                        $request->user()->email;
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

            $lines[] = '• ' . $command;
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
        string $message
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
        string $message
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
                $chunk
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