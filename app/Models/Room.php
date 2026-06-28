<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';
    protected $fillable = [
        'floor_id',
        'room_number',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'floor_id' => 'integer',
            'capacity' => 'integer',
        ];
    }

    public function floor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}

