<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentNotification extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'triggered_for_user_id',
        'notification_type',
        'title',
        'message',
        'action_url',
        'action_label',
        'priority',
        'is_read',
        'is_dismissed',
        'read_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'     => 'array',
            'is_read'      => 'boolean',
            'is_dismissed' => 'boolean',
            'read_at'      => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function triggeredFor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_for_user_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_dismissed', false);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('priority', 'critical');
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
