<?php

use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;


Route::prefix("")->group(function () {
    includeRouteFiles(__DIR__ ."/api/v1");
});

Route::middleware('throttle:telegram-webhook')->post('/telegram/webhook', TelegramWebhookController::class);