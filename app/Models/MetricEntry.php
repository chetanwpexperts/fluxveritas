<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricEntry extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'department_id',
        'metric_id',
        'value',
        'entry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'value'      => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function metric(): BelongsTo
    {
        return $this->belongsTo(DepartmentMetric::class, 'metric_id');
    }
}
