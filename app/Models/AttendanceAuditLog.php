<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'student_attendance_id',
        'changed_by',
        'from_status',
        'to_status',
        'from_source',
        'to_source',
    ];

    protected function casts(): array
    {
        return [
            'student_attendance_id' => 'integer',
            'changed_by' => 'integer',
        ];
    }

    public function attendance()
    {
        return $this->belongsTo(StudentAttendance::class, 'student_attendance_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
