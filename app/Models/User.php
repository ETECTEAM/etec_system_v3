<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'requires_onboarding',
        'onboarding_completed_at',
        'last_login_at',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'status' => UserStatus::class,
            'verified_at' => 'datetime',
            'password' => 'hashed',
            'requires_onboarding' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OtpVerification::class);
    }

    public function authAuditLogs(): HasMany
    {
        return $this->hasMany(AuthAuditLog::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id', 'id');
    }

    public function instructorData(): HasOne
    {
        return $this->hasOne(InstructorData::class, 'user_id', 'id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(Photo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function passwordResetRecipient(): ?string
    {
        if ($this->recovery_verified && $this->recovery_email) {
            return $this->recovery_email;
        }

        return null;
    }

    // Only send the reset link to a verified recovery email.
    public function sendPasswordResetNotification($token): void
    {
        $recipient = $this->passwordResetRecipient();

        if (! $recipient) {
            return;
        }

        Notification::route('mail', $recipient)
            ->notify(new ResetPasswordNotification($token, $this->email));
    }
}
