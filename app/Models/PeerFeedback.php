<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerFeedback extends Model
{
    protected $table = 'peer_feedbacks';

    protected $fillable = [
        'employee_id', 'reviewer_id', 'organization_id',
        'review_period', 'review_year',
        'collaboration_score', 'reliability_score',
        'knowledge_score', 'helpfulness_score',
        'strength', 'improvement',
        'token', 'token_expires_at',
        'submitted_at', 'is_submitted',
    ];

    protected function casts(): array
    {
        return [
            'is_submitted'     => 'boolean',
            'submitted_at'     => 'datetime',
            'token_expires_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Returns 0-100 percentage (scores are 1-5, multiply by 20)
    public function getAverageScoreAttribute(): float
    {
        $scores = array_filter([
            $this->collaboration_score,
            $this->reliability_score,
            $this->knowledge_score,
            $this->helpfulness_score,
        ]);
        if (empty($scores)) return 0;
        return round((array_sum($scores) / count($scores)) * 20, 1);
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
