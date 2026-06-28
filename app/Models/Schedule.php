<?php

namespace App\Models;

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
}
