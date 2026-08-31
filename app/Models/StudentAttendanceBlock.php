<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @see database/migrations/2026_08_31_000002_create_student_attendance_block_table.php
 */
class StudentAttendanceBlock extends Model
{
    public const TYPE_ABSENCE = 'absence';

    public const TYPE_HARD_LOCK = 'hard_lock';

    public const REASON_SOFT = 'Attendance locked: reached 3 absences this month. Please meet admin for approval.';

    public const REASON_HARD = 'Hard lock: exceeded the 2 extra absences after admin approval. Please contact super admin.';

    public const COMMENT_HARD_LOCK = 'Hard lock: exceeded the 2 extra absences after admin approval';

    public const COMMENT_UNLOCKED = 'Unlocked by super admin';

    protected $table = 'student_attendance_block';

    protected $fillable = [
        'student_id',
        'student_tel',
        'course_id',
        'study_class_id',
        'block_type',
        'is_approved',
        'blocked_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'admin_comment',
        'cycle_start_date',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'course_id' => 'integer',
            'study_class_id' => 'integer',
            'is_approved' => 'boolean',
            'blocked_at' => 'datetime',
            'approved_at' => 'datetime',
            'approved_by' => 'integer',
            'rejected_at' => 'datetime',
            'cycle_start_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * An "open" block is the only kind that locks attendance or blocks a
     * duplicate: not yet approved and not rejected.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_approved', false)->whereNull('rejected_at');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('block_type', $type);
    }

    public function scopeForCycleKey(Builder $query, string $tel, int $courseId): Builder
    {
        return $query->where('student_tel', $tel)->where('course_id', $courseId);
    }

    public function isOpen(): bool
    {
        return ! $this->is_approved && $this->rejected_at === null;
    }

    /**
     * Derived badge for the blacklist UI.
     */
    public function statusLabel(): string
    {
        if ($this->rejected_at !== null) {
            return 'rejected';
        }

        if ($this->is_approved) {
            return $this->block_type === self::TYPE_HARD_LOCK ? 'unlocked' : 'approved';
        }

        return 'pending';
    }
}
