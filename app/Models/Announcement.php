<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'organization_id', 'posted_by', 'title', 'message',
        'priority', 'audience', 'department_id', 'team_id',
        'is_pinned', 'expires_at',
    ];

    protected $casts = [
        'is_pinned'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('organization_id', $user->organization_id)
            ->active()
            ->where(function ($q) use ($user) {
                $q->where('audience', 'org')
                  ->orWhere(function ($dq) use ($user) {
                      $dq->where('audience', 'department')
                         ->where('department_id', $user->department_id);
                  })
                  ->orWhere(function ($tq) use ($user) {
                      $tq->where('audience', 'team')
                         ->where('team_id', $user->team_id);
                  });
            });
    }

    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getAudienceLabelAttribute(): string
    {
        return match ($this->audience) {
            'org'        => 'Everyone',
            'department' => $this->department?->name ?? 'Department',
            'team'       => $this->team?->name ?? 'Team',
            default      => 'Everyone',
        };
    }
}
