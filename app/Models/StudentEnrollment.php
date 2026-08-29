<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $table = 'student_enrollments';

    protected $fillable = [
        'study_class_id',
        'student_id',
        'course_id',
        'term_id',
        'time_id',
        'enrollment_status',
        'payment_status',
        'source',
        'fee_amount',
        'document_fee_amount',
        'amount_paid',
        'enrolled_at',
        'paid_at',
        'no_room_and_instructor', // bool
        'no_instructor', // bool
        'no_room', // bool
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'student_id' => 'integer',
            'course_id' => 'integer',
            'term_id' => 'integer',
            'time_id' => 'integer',
            'fee_amount' => 'decimal:2',
            'document_fee_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'enrolled_at' => 'datetime',
            'paid_at' => 'datetime',
            'no_room_and_instructor' => 'boolean',
            'no_instructor' => 'boolean',
            'no_room' => 'boolean',
        ];
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'student_enrollment_id');
    }

    public function latestAttendance(): HasOne
    {
        return $this->hasOne(StudentAttendance::class, 'student_enrollment_id')->latestOfMany('attendance_date');
    }

    // The course/term/time the student asked for at registration time -
    // only meaningful while study_class_id is null (see
    // RegisterStudentForSchedule::saveUnassignedEnrollment); once a class is
    // assigned, prefer studyClass's own course/term/time for display.
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function time()
    {
        return $this->belongsTo(Time::class);
    }

    public function score()
    {
        return $this->hasOne(StudentScore::class, 'student_enrollment_id');
    }
}
