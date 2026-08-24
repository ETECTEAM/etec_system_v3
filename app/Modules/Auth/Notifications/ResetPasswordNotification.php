<?php

namespace App\Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers the password-reset link to the account's verified recovery email
 * when one exists, otherwise to the login email. Built instead of reusing
 * Laravel's default Illuminate\Auth\Notifications\ResetPassword because that
 * class resolves its own recipient via $notifiable->getEmailForPasswordReset(),
 * which doesn't exist on the AnonymousNotifiable used to route this mail
 * (see User::sendPasswordResetNotification()).
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
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
        $url = route('password.reset', ['token' => $this->token, 'email' => $this->loginEmail]);

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for the account '.$this->loginEmail.'.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
