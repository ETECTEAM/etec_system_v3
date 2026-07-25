<?php

namespace App\Modules\Auth\Listeners;

use App\Models\Notification;
use App\Modules\Auth\Events\PendingUserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued listener that mirrors the Telegram admin approval request onto the
 * dashboard notification feed for super_admin/admin users.
 */
class CreateAdminApprovalNotification implements ShouldQueue
{
    public function handle(PendingUserRegistered $event): void
    {
        Notification::create([
            'title' => 'New Instructor Registration',
            'message' => "{$event->user->name} ({$event->user->email}) — verification code: {$event->plainCode}",
            'type' => 'instructor_approval',
            'otp_verification_id' => $event->otp->id,
        ]);
    }
}
