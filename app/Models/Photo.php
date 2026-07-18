<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
