<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RECORDED = 'recorded';

    public const STATUS_AUTO_RECORDED = 'auto_recorded';

    public const STATUS_PRE_ATTENDANCE = 'pre_attendance';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_MISSED = 'missed';

    protected $fillable = [
        'study_class_id',
        'instructor_id',
        'session_date',
        'scheduled_start',
        'scheduled_end',
        'status',
        'recorded_at',
        'grace_minutes_used',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'instructor_id' => 'integer',
            'session_date' => 'date',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'recorded_at' => 'datetime',
            'grace_minutes_used' => 'integer',
        ];
    }

    // Only sessions dated after the class's first real tracked day are auto-recorded.
    public function scopeAutoRecordArmed(Builder $query): Builder
    {
        return $query->whereHas('studyClass', fn (Builder $class) => $class
            ->whereNotNull('auto_record_started_on')
            ->whereColumn('study_classes.auto_record_started_on', '<', 'class_sessions.session_date'));
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
