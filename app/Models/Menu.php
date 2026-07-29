<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'page_id',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'parent_id' => 'integer',
        'position' => 'integer',
    ];

    protected $appends = [
        'resolved_url',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('position')->orderBy('id');
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
