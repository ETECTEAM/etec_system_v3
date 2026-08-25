<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'official_leave_id',
        'before',
        'after',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'official_leave_id' => 'integer',
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officialLeave(): BelongsTo
    {
        return $this->belongsTo(OfficialLeave::class);
    }
}
