<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'block_type',
        'status',
        'blocked_at',
        'approved_at',
        'approved_by',
        'admin_comment',
        'cycle_started_at',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'course_id' => 'integer',
            'approved_by' => 'integer',
            'blocked_at' => 'datetime',
            'approved_at' => 'datetime',
            'cycle_started_at' => 'datetime',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
