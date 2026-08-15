<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollConfig extends Model
{
    use HasFactory;

    public const PRICE_TYPE_UNIT = 'unit';

    public const PRICE_TYPE_COURSE = 'course';

    protected $fillable = [
        'course_id',
        'time_id',
        'status',
        'start_date',
        'unit_price',
        'course_price',
        'selected_price_type',
        'document_price',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'time_id' => 'integer',
            'start_date' => 'date',
            'unit_price' => 'decimal:2',
            'course_price' => 'decimal:2',
            'document_price' => 'decimal:2',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function time()
    {
        return $this->belongsTo(Time::class);
    }

    // The actually-charged price - unit_price and course_price are both kept
    // regardless of which is selected, so switching the selection later (or
    // auditing what was charged) never loses the other figure.
    public function resolvedPrice(): float
    {
        return $this->selected_price_type === self::PRICE_TYPE_UNIT
            ? (float) $this->unit_price
            : (float) $this->course_price;
    }
}
