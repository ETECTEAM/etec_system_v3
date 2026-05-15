<?php

namespace App\Listeners;

use App\Events\PendingUserRegistered;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTelegramAdminApproval implements ShouldQueue
{
    public function __construct(private readonly TelegramService $telegramService) {}

    public function handle(PendingUserRegistered $event): void
    {
        $this->telegramService->sendAdminApprovalRequest(
            $event->user,
            $event->otp,
            $event->plainCode,
        );
    }
}
