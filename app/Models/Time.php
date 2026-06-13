<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    //
    protected $fillable = [
        'time_name',
        'term_id',
    ];
    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
