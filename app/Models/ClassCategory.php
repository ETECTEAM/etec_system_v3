<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassCategory extends Model
{
    protected $table = 'class_category';
    protected $primaryKey = 'class_category_id';

    protected $fillable = [
        'class_type_id',
        'category_name',
        'category_code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function classType(): BelongsTo
    {
        return $this->belongsTo(ClassType::class, 'class_type_id', 'class_type_id');
    }
}