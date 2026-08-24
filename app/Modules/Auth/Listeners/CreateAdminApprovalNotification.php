<?php

namespace App\Modules\Auth\Listeners;

use App\Models\Notification;
use App\Modules\Auth\Events\PendingUserRegistered;
use App\Modules\Notification\Events\NotificationsUpdated;
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
            'message' => "{$event->user->name} ({$this->maskEmail($event->user->email)}) — verification code: {$event->plainCode}",
            'type' => 'instructor_approval',
            'otp_verification_id' => $event->otp->id,
        ]);

        NotificationsUpdated::dispatch();
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return $email;
        }

        $visible = mb_substr($localPart, 0, 4);

        return "{$visible}****@{$domain}";
    }
}
