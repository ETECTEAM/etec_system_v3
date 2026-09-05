<?php
// app/Models/CourseTrack.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTrack extends Model
{
    use HasFactory;

    protected $fillable = ['sub_category_id', 'class_type_id', 'name', 'slug', 'status'];

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    // The Class Type this track's courses are taught as. NULL = no explicit
    // mapping (Enroll Config falls back to the default schedule set).
    public function classType()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id', 'class_type_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
