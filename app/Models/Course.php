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
        'description',
        'level',
        'duration',
        'thumbnail',
        'language',
        'certificate_available',
        'status'
    ];

    protected $casts = [
        'certificate_available' => 'boolean',
        'duration' => 'integer',
    ];

    public function track()
    {
        return $this->belongsTo(CourseTrack::class, 'course_track_id');
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class);
    }
}
