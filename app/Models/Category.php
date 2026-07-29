<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    public function courses()
    {
        return $this->hasManyThrough(Course::class, SubCategory::class);
    }
}