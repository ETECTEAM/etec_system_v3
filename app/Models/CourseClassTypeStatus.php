<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Whether a course is open for enrollment under one class type. A missing row
 * means open, so only a course an admin has actually closed (or re-opened)
 * under a class type ever has one.
 */
class CourseClassTypeStatus extends Model
{
    protected $fillable = ['course_id', 'class_type_id', 'status'];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'class_type_id' => 'integer',
        ];
    }
}
