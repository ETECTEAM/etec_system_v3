<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    //
    protected $fillable = ['term_name'];

    public function times()
    {
        return $this->hasMany(Time::class);
    }
}
