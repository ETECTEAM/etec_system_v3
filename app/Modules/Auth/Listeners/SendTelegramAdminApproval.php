<?php

namespace App\Modules\Auth\Listeners;

use App\Modules\Auth\Events\PendingUserRegistered;
use App\Modules\Auth\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued listener that asks Telegram admins to approve or reject a new user.
 */
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
