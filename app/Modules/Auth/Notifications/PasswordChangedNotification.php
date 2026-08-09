<?php

namespace App\Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a completed password reset, sent to the recovery email since
 * that's the only address that could have received the reset link itself.
 */
class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $loginEmail,
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
            ->subject('Your password has been changed')
            ->line('The password for your account ('.$this->loginEmail.') was just changed.')
            ->line('All other active sessions have been signed out.')
            ->line('If you did not make this change, contact an administrator immediately.');
    }
}
