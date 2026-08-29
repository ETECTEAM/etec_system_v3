<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Auth\Services\TokenExpirationService;
use Carbon\Carbon;
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
        'last_login_at',
        'created_by',
        'access_expires_at',
        'access_renewed_at',
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

    protected $casts = [
        'access_expires_at' => 'datetime',
        'access_renewed_at' => 'datetime',
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

    // The effective deadline is derived from access_renewed_at (when the token
    // was last issued) plus the role's configured lifetime from
    // config('auth.token_expiration.roles'). Falls back to the stored
    // access_expires_at for any legacy rows that only have that column set.
    // Roles with no configured lifetime (e.g. student) have no window (null),
    // so they never expire.
    public function accessExpiresAt(): ?Carbon
    {
        if ($this->access_renewed_at !== null) {
            $duration = app(TokenExpirationService::class)->durationFor($this);

            if ($duration !== null) {
                return $this->access_renewed_at->copy()->add($duration);
            }
        }

        return $this->access_expires_at;
    }

    public function isAccessExpired(): bool
    {
        $expiresAt = $this->accessExpiresAt();

        if ($expiresAt === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($expiresAt);
    }

    // A user's token/session is invalid (and they should be signed out) when
    // the deadline has passed, or when the deadline isn't strictly ahead of
    // the last renewal — e.g. access_expires_at <= access_renewed_at, an
    // inverted/inconsistent state. Roles with no configured lifetime
    // (e.g. student) have a null deadline and never expire.
    public function accessWindowInvalid(): bool
    {
        if ($this->access_expires_at === null) {
            return false;
        }

        if ($this->access_renewed_at !== null && $this->access_expires_at->lte($this->access_renewed_at)) {
            return true;
        }

        return $this->isAccessExpired();
    }
}
