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

    protected $fillable = [
        'study_class_id',
        'student_enrollment_id',
        'student_id',
        'tracked_by',
        'attendance_date',
        'status',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'student_enrollment_id' => 'integer',
            'student_id' => 'integer',
            'tracked_by' => 'integer',
            'attendance_date' => 'date',
        ];
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
}
