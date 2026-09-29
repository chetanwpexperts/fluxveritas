<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PerformanceFeedback extends Model
{
    protected $table = 'performance_feedbacks';

    protected $fillable = [
        'employee_id', 'manager_id', 'organization_id',
        'review_period', 'review_year',
        'delivery_score', 'timeliness_score',
        'availability_score', 'collaboration_score',
        'notable_achievement', 'area_of_improvement',
        'special_circumstances', 'manager_confirmed',
        'status', 'submitted_at', 'deadline', 'employee_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'manager_confirmed'  => 'boolean',
            'submitted_at'       => 'datetime',
            'deadline'           => 'datetime',
            'employee_viewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function statement(): HasOne
    {
        return $this->hasOne(EmployeeStatement::class, 'feedback_id');
    }

    // 1=100%, 2=75%, 3=50%, 4=25%
    public function getImpliedScoreAttribute(): float
    {
        $map = [1 => 100, 2 => 75, 3 => 50, 4 => 25];
        $scores = [
            $this->delivery_score,
            $this->timeliness_score,
            $this->availability_score,
            $this->collaboration_score,
        ];
        $total = collect($scores)->sum(fn($s) => $map[$s] ?? 75);
        return round($total / count($scores), 1);
    }

    public function getDeliveryLabelAttribute(): string
    {
        return match($this->delivery_score) {
            1 => 'All completed',
            2 => 'Most completed',
            3 => 'Partially completed',
            4 => 'Few completed',
            default => 'Not assessed',
        };
    }

    public function getTimelinessLabelAttribute(): string
    {
        return match($this->timeliness_score) {
            1 => 'Always on time',
            2 => 'Usually on time',
            3 => 'Sometimes delayed',
            4 => 'Often delayed',
            default => 'Not assessed',
        };
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return match($this->availability_score) {
            1 => 'Always available',
            2 => 'Usually available',
            3 => 'Sometimes unavailable',
            4 => 'Often unavailable',
            default => 'Not assessed',
        };
    }

    public function getCollaborationLabelAttribute(): string
    {
        return match($this->collaboration_score) {
            1 => 'Always helped',
            2 => 'Usually helped',
            3 => 'Sometimes helped',
            4 => 'Rarely helped',
            default => 'Not assessed',
        };
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status === 'draft';
    }
}
