<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteVideo extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_path',
        'thumbnail_path',
        'duration',
        'views_count',
        'sort_order',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'video_url',
        'thumbnail_url',
    ];

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? time();

        return '/storage/'.ltrim($this->video_path, '/').'?v='.$version;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? time();

        return '/storage/'.ltrim($this->thumbnail_path, '/').'?v='.$version;
    }
}
