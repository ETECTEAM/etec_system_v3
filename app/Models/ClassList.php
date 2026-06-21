<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassList extends Model
{
    use HasFactory;

    /**
     * Table name doesn't follow Laravel's pluralization convention,
     * so it must be set explicitly.
     */
    protected $table = 'class_list';

    protected $fillable = [
        'teacher_id',
        'course_id',
        'lesson_id',
        'term_id',
        'time_id',
        'building_id',
        'floor_id',
        'room_id',
        'class_type_id',
        'student_count',
        'status',
    ];

    protected $casts = [
        'student_count' => 'integer',
    ];

    // Relationships

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // public function course()
    // {
    //     return $this->belongsTo(Course::class);
    // }

    // public function lesson()
    // {
    //     return $this->belongsTo(Lesson::class);
    // }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function time()
    {
        return $this->belongsTo(Time::class);
    }

    // public function building()
    // {
    //     return $this->belongsTo(Building::class);
    // }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function classType()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id', 'class_type_id');
    }
}