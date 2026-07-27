<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_name',
        'school_logo',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->school_logo) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? time();

        return '/storage/'.ltrim($this->school_logo, '/').'?v='.$version;
    }
}
