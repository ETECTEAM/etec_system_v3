<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_ADMIN_EDIT = 'admin_edit';

    public const SOURCE_QR = 'qr_code';

    protected $fillable = [
        'study_class_id',
        'student_enrollment_id',
        'attendance_session_id',
        'student_id',
        'tracked_by',
        'attendance_date',
        'latitude',
        'longitude',
        'location_accuracy',
        'distance_from_class',
        'ip_address',
        'user_agent',
        'browser',
        'operating_system',
        'device_type',
        'device_identifier',
        'status',
        'locked',
        'lock_reason',
        'locked_block_id',
        'verification_status',
        'verification_reason',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'student_enrollment_id' => 'integer',
            'attendance_session_id' => 'integer',
            'student_id' => 'integer',
            'tracked_by' => 'integer',
            'attendance_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
            'distance_from_class' => 'decimal:2',
            'locked' => 'boolean',
            'locked_block_id' => 'integer',
        ];
    }

    public function attendanceSession()
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function tracker()
    {
        return $this->belongsTo(User::class, 'tracked_by');
    }

    public function lockedBlock()
    {
        return $this->belongsTo(StudentAttendanceBlock::class, 'locked_block_id');
    }
}
