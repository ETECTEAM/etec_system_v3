<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyClass extends Model
{
    use HasFactory;

    protected $table = 'study_classes';

    protected $fillable = [
        'title',
        'course_id',
        'lesson_id',
        'teacher_id',
        'room_id',
        'class_type',
        'status',
        'study_days',
        'start_time',
        'end_time',
        'capacity',
        'price',
        'document_price',
        'enrollment_start_date',
        'start_date',
        'end_date',
        'meeting_link',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'lesson_id' => 'integer',
            'teacher_id' => 'integer',
            'room_id' => 'integer',
            'study_days' => 'array',
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'document_price' => 'decimal:2',
            'enrollment_start_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
