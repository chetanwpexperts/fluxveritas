<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    protected $fillable = [
        'organization_id', 'title', 'slug', 'category',
        'department_type', 'seniority_level', 'requires_github',
        'metric_weights', 'work_log_categories', 'is_active',
        'is_template', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_github'     => 'boolean',
            'metric_weights'      => 'array',
            'work_log_categories' => 'array',
            'is_active'           => 'boolean',
            'is_template'         => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'designation', 'slug');
    }

    public static function forOrganization(int $orgId)
    {
        return static::where(function ($q) use ($orgId) {
            $q->where('organization_id', $orgId)->orWhereNull('organization_id');
        })
        ->where('is_active', true)
        ->orderBy('category')
        ->orderBy('sort_order')
        ->get();
    }
}
