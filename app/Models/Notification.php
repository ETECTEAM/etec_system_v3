<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    // Named away from the `notifications` table Laravel's Notifiable trait
    // expects (notifiable_type/notifiable_id columns this table doesn't
    // have) - $user->notifications would otherwise query the wrong shape.
    protected $table = 'dashboard_notifications';

    protected $fillable = [
        'title',
        'message',
        'type',
        'otp_verification_id',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function otpVerification(): BelongsTo
    {
        return $this->belongsTo(OtpVerification::class);
    }
}
