<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasOrganizationScope;
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'type',
        'work_mode',
        'color',
        'icon',
        'description',
        'is_active',
        'head_user_id',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings'  => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(DepartmentMetric::class);
    }

    public function isGithubEnabled(): bool
    {
        return in_array($this->work_mode, ['github', 'hybrid']);
    }

    public function isManualEnabled(): bool
    {
        return in_array($this->work_mode, ['manual', 'hybrid']);
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'tech'       => 'Technology',
            'sales'      => 'Sales',
            'hr'         => 'Human Resources',
            'finance'    => 'Finance',
            'operations' => 'Operations',
            'design'     => 'Design',
            'marketing'  => 'Marketing',
            default      => 'Other',
        };
    }
}
