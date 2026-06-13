<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassType extends Model
{
    protected $table = 'class_type';
    protected $primaryKey = 'class_type_id';

    protected $fillable = [
        'type_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['classCategories'];

    public function classCategories(): HasMany
    {
        return $this->hasMany(ClassCategory::class, 'class_type_id', 'class_type_id');
    }

    public function activeCategories(): HasMany
    {
        return $this->hasMany(ClassCategory::class, 'class_type_id', 'class_type_id')
            ->where('is_active', true);
    }
}