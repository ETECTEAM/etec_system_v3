<?php
// app/Models/SubCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'slug', 'status'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tracks()
    {
        return $this->hasMany(CourseTrack::class);
    }

    public function courses()
    {
        return $this->hasManyThrough(Course::class, CourseTrack::class);
    }
}