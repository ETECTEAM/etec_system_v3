<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'class_type_id',
        'term_id',
    ];

    public function classType()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id', 'class_type_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function times()
    {
        return $this->belongsToMany(Time::class, 'schedule_time');
    }

    public function scopeForTime(Builder $query, int $timeId): Builder
    {
        return $query->whereHas('times', fn (Builder $q) => $q->where('times.id', $timeId));
    }

    public function scopeForAnyTime(Builder $query, array $timeIds): Builder
    {
        return $query->whereHas('times', fn (Builder $q) => $q->whereIn('times.id', $timeIds));
    }
}
