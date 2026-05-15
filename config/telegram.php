<?php

return [
    'bots' => [
        'default' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
        ],
    ],
    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
];
