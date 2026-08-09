<?php

namespace App\Modules\Account\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security alert sent to the login email whenever the account's recovery
 * email is added or changed - never the verification link itself, so a
 * compromised recovery address alone can't silently take over the account.
 */
class RecoveryEmailChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $newRecoveryEmail,
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
            ->subject('Your recovery email was changed')
            ->line('The recovery email on your account was just changed to '.$this->maskEmail($this->newRecoveryEmail).'.')
            ->line('It will be used to deliver password-reset links once verified.')
            ->line('If you did not make this change, contact an administrator immediately.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        $visible = mb_substr($local, 0, 2);

        return $visible.str_repeat('*', max(1, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
