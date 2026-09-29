<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerAccountabilityScore extends Model
{
    protected $table = 'manager_accountability_scores';

    protected $fillable = [
        'manager_id', 'organization_id',
        'review_period', 'review_year',
        'feedbacks_due', 'feedbacks_submitted',
        'feedbacks_on_time', 'bias_flags',
        'accountability_score', 'breakdown',
    ];

    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
