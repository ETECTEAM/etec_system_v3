<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'page_id',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    protected $appends = [
        'resolved_url',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getResolvedUrlAttribute(): ?string
    {
        if (! $this->page?->slug) {
            return null;
        }

        if ($this->page->slug === 'home') {
            return '/home';
        }

        return route('pages.show', $this->page->slug, false);
    }
}
