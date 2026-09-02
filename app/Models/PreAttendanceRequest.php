<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreAttendanceRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'study_class_id',
        'class_session_id',
        'requested_by',
        'reviewed_by',
        'session_date',
        'session_status',
        'status',
        'note',
        'requested_at',
        'reviewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'class_session_id' => 'integer',
            'requested_by' => 'integer',
            'reviewed_by' => 'integer',
            'session_date' => 'date',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
