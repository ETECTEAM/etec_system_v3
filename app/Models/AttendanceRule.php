<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @see database/migrations/2026_08_31_000001_create_attendance_rules_table.php
 */
class AttendanceRule extends Model
{
    public const TYPE_ABSENCE = 'absence';

    public const TYPE_PERMISSION = 'permission';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_BOTH = 'both';

    protected $fillable = [
        'rule_type',
        'limit_count',
        'period_type',
        'start_date',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'limit_count' => 'integer',
            'start_date' => 'date',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $ruleType): Builder
    {
        return $query->where('rule_type', $ruleType);
    }
}
