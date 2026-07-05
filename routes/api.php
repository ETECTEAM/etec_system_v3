<?php

use App\Modules\Auth\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:telegram-webhook')->post('/telegram/webhook', TelegramWebhookController::class);