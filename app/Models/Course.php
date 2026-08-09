<?php
// app/Models/Course.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_track_id',
        'title',
        'slug',
        'level',
        'thumbnail',
        'status'
    ];

    public function track()
    {
        return $this->belongsTo(CourseTrack::class, 'course_track_id');
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class);
    }

    public function enrollConfig()
    {
        return $this->hasOne(CourseEnrollConfig::class);
    }
}
