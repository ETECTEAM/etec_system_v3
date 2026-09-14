<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'study_class_id',
        'qr_token',
        'attendance_date',
        'started_at',
        'expires_at',
        'status',
        'created_by',
        'stopped_by',
        'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'attendance_date' => 'date',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_by' => 'integer',
            'stopped_by' => 'integer',
            'stopped_at' => 'datetime',
        ];
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stopper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stopped_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }
}
