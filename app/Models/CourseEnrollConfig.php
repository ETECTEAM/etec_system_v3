<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'status',
        'start_date',
        'price',
        'document_price',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'start_date' => 'date',
            'price' => 'decimal:2',
            'document_price' => 'decimal:2',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
