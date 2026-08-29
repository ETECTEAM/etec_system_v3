<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'student_id',
        'changed_by',
        'action',
        'previous_status',
        'new_status',
        'reason',
        'ip_address',
        'study_class_id',
        'target_study_class_id',
        'effective_date',
        'start_date',
        'end_date',
        'before_payload',
        'after_payload',
    ];

    protected function casts(): array
    {
        return [
            'attendance_id' => 'integer',
            'student_id' => 'integer',
            'changed_by' => 'integer',
            'study_class_id' => 'integer',
            'target_study_class_id' => 'integer',
            'effective_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'before_payload' => 'array',
            'after_payload' => 'array',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StudentAttendance::class, 'attendance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
