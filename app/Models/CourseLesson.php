<?php
// app/Models/CourseLesson.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'slug',
        'description',
        'content',
        'video_url',
        'duration',
        'order',
        'status'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}