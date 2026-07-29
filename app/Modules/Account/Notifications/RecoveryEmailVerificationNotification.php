<?php

namespace App\Modules\Account\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers the signed verification link for a newly-submitted recovery
 * email. Clicking it is the proof of mailbox access that flips
 * User::recovery_verified to true (see AccountSecurityController).
 */
class RecoveryEmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $verificationUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your recovery email')
            ->line('You added this address as the recovery email for your account.')
            ->action('Verify Recovery Email', $this->verificationUrl)
            ->line('This link expires in 24 hours.')
            ->line('If you did not request this, you can ignore this email.');
    }
}
