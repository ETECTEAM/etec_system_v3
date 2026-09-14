<?php

return [
    'otp_bot_token' => env('TELEGRAM_OTP_BOT_TOKEN'),
    'error_bot_token' => env('TELEGRAM_ERROR_BOT_TOKEN'),
    'otp_chat_id' => env('TELEGRAM_OTP_CHAT_ID'),
    'error_chat_id' => env('TELEGRAM_ERROR_CHAT_ID'),
    'personal_chat_id' => env('TELEGRAM_PERSONAL_CHAT_ID'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
];
