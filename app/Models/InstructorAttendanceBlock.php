<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAttendanceBlock extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED_UNBLOCK = 'approved_unblock';

    /** Every status except this one still blocks attendance tracking. */
    public const BLOCKING_STATUSES = [self::STATUS_ACTIVE, self::STATUS_PENDING_REVIEW];

    /**
     * Instructor's claim when requesting an unblock, and the reason an admin
     * actually approved the block under (see docs/instructor-attendance-block-approve-after-permission.md).
     */
    public const REASON_GENERAL = 'general';

    public const REASON_PERMISSION = 'permission';

    public const UNBLOCK_REASONS = [self::REASON_GENERAL, self::REASON_PERMISSION];

    public const REVIEWED_REASONS = [self::REASON_GENERAL, self::REASON_PERMISSION];

    protected $fillable = [
        'instructor_id',
        'triggered_by_session_id',
        'reason',
        'unblock_reason_type',
        'status',
        'blocked_at',
        'unblock_requested_at',
        'reviewed_by',
        'reviewed_at',
        'reviewed_reason_type',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'instructor_id' => 'integer',
            'triggered_by_session_id' => 'integer',
            'reviewed_by' => 'integer',
            'blocked_at' => 'datetime',
            'unblock_requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function triggeredBySession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'triggered_by_session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
