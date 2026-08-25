<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficialLeave extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVOKED = 'revoked';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_REVOKED,
    ];

    protected $fillable = [
        'student_id',
        'study_class_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejection_note',
        'revoked_by',
        'revoked_at',
        'revoked_note',
        'leave_request_session_id',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'study_class_id' => 'integer',
            'leave_request_session_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'rejected_by' => 'integer',
            'revoked_by' => 'integer',
            'revoked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LeaveRequestSession::class, 'leave_request_session_id');
    }
}
