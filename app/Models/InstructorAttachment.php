<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAttachment extends Model
{
    protected $fillable = [
        'instructor_id',
        'type',
        'title',
        'file_name',
        'file_path',
        'file_mime',
        'file_size',
        'is_primary',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return '/storage/'.ltrim($this->file_path, '/');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(InstructorData::class, 'instructor_id');
    }
}
