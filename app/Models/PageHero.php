<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageHero extends Model
{
    protected $fillable = [
        'page_id',
        'title',
        'subtitle',
        'description',
        'background_image',
        'primary_button_text',
        'primary_button_url',
        'secondary_button_text',
        'secondary_button_url',
        'overlay_opacity',
        'text_alignment',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'overlay_opacity' => 'integer',
    ];

    protected $appends = [
        'background_image_url',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        if (! $this->background_image) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? time();

        return '/storage/'.ltrim($this->background_image, '/').'?v='.$version;
    }
}
