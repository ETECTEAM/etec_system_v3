<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table ='rooms';
    protected $fillable = [
        'floor_id',
        'room_number',
        'capacity',
        'status',
    ];
}
