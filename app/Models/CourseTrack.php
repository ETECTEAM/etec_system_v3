<?php
// app/Models/CourseTrack.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTrack extends Model
{
    use HasFactory;

    protected $fillable = ['sub_category_id', 'name', 'slug', 'status'];

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}