<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackBiasReport extends Model
{
    protected $table = 'feedback_bias_reports';

    protected $fillable = [
        'feedback_id', 'employee_id', 'manager_id',
        'organization_id', 'review_period', 'review_year',
        'ai_score', 'manager_implied_score', 'peer_score',
        'peer_count', 'deviation', 'bias_detected',
        'bias_confidence', 'bias_type', 'bias_reason',
        'evidence', 'ceo_reviewed', 'ceo_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'bias_detected'   => 'boolean',
            'ceo_reviewed'    => 'boolean',
            'ceo_reviewed_at' => 'datetime',
            'evidence'        => 'array',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(PerformanceFeedback::class, 'feedback_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
