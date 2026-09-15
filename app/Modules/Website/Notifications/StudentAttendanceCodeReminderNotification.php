<?php

namespace App\Modules\Website\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAttendanceCodeReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $attendanceCode) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student Portal attendance code recovery')
            ->line('We received a request to recover your Student Portal attendance code.')
            ->line('Your new attendance code is: '.$this->attendanceCode)
            ->line('Your previous code no longer works.')
            ->line('If you did not request this, no further action is required.');
    }
}
