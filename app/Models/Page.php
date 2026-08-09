<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hero(): HasOne
    {
        return $this->hasOne(PageHero::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }
}
