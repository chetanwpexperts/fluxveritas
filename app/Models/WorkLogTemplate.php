<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLogTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'category',
        'title',
        'description',
        'duration_minutes',
        'output_value',
        'tags',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'tags'             => 'array',
            'usage_count'      => 'integer',
            'duration_minutes' => 'integer',
            'output_value'     => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
