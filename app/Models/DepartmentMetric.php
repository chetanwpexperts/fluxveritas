<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentMetric extends Model
{
    protected $fillable = [
        'department_id',
        'organization_id',
        'metric_name',
        'metric_label',
        'metric_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MetricEntry::class, 'metric_id');
    }
}
