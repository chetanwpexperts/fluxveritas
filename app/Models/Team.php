<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = [
        'organization_id',
        'department_id',
        'team_lead_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teamLead(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'team_id')->where('is_active', true);
    }

    public function allMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Task::class,
            User::class,
            'team_id',
            'assigned_to',
            'id',
            'id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    public function isAtCapacity(): bool
    {
        return $this->getMemberCount() >= 10;
    }

    public function hasMinimumMembers(): bool
    {
        return $this->getMemberCount() >= 3;
    }

    public function getLoggedTodayCount(): int
    {
        $memberIds = $this->members()->pluck('id');
        return WorkLog::whereIn('user_id', $memberIds)
            ->where('log_date', today())
            ->distinct('user_id')
            ->count('user_id');
    }

    public function getActiveTasksCount(): int
    {
        $memberIds = $this->members()->pluck('id');
        return Task::whereIn('assigned_to', $memberIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }
}
