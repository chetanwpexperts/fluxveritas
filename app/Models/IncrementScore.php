<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncrementScore extends Model
{
    protected $fillable = [
        'user_id', 'organization_id', 'policy_id', 'score_month',
        'raw_score', 'weighted_score', 'criteria_breakdown',
        'anti_gaming_penalty', 'final_score', 'is_adjusted',
        'adjustment_reason', 'calculated_at',
    ];

    protected $casts = [
        'criteria_breakdown' => 'array',
        'score_month' => 'date',
        'is_adjusted' => 'boolean',
        'final_score' => 'float',
        'raw_score' => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function policy() { return $this->belongsTo(IncrementPolicy::class); }
}
