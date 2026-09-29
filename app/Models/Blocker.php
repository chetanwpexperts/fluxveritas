<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blocker extends Model
{
    protected $fillable = [
        'organization_id',
        'project_id',
        'reported_by',
        'blocked_user_id',
        'blocking_user_id',
        'blocker_type',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'external_person_name',
        'external_person_company',
        'external_person_contact',
        'evidence_notes',
        'ownership_disputed',
        'dispute_reason',
        'dispute_raised_at',
        'dispute_resolved_at',
        'resolution_proof',
        'resolved_by_external',
        'days_to_resolve',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'           => 'datetime',
            'resolved_at'        => 'datetime',
            'dispute_raised_at'  => 'datetime',
            'dispute_resolved_at' => 'datetime',
            'reminder_sent_at'   => 'datetime',
            'ownership_disputed' => 'boolean',
            'resolved_by_external' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }

    public function blockingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocking_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(BlockerResponse::class)->orderBy('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('blocked_user_id', $userId);
    }

    public function isExternal(): bool
    {
        return !is_null($this->external_person_name)
            || $this->blocker_type === 'external_vendor'
            || $this->blocker_type === 'external_person';
    }

    public function isOwnershipDisputed(): bool
    {
        return $this->ownership_disputed === true;
    }

    public function daysOpen(): int
    {
        return (int) $this->created_at->diffInDays($this->resolved_at ?? now());
    }

    public function needsAttention(): bool
    {
        return $this->status === 'open' && $this->daysOpen() >= 3;
    }

    public function ageInDays(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high'     => 'amber',
            'medium'   => 'blue',
            default    => 'gray',
        };
    }
}
