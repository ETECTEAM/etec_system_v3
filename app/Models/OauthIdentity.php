<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthIdentity extends Model
{
    protected $fillable = ['provider', 'provider_id', 'provider_email'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
