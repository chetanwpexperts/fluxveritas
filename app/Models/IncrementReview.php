<?php
namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;

class IncrementReview extends Model
{
    use HasOrganizationScope;
    protected $fillable = [
        'user_id', 'organization_id', 'policy_id', 'review_year', 'review_period',
        'months_included', 'months_excluded', 'avg_score', 'recommended_increment',
        'manager_recommendation', 'manager_notes', 'final_increment', 'ceo_notes',
        'override_reason', 'status', 'manager_reviewed_at', 'ceo_approved_at',
        'employee_notified_at',
    ];

    protected $casts = [
        'months_included' => 'array',
        'months_excluded' => 'array',
        'avg_score' => 'float',
        'manager_reviewed_at' => 'datetime',
        'ceo_approved_at' => 'datetime',
        'employee_notified_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function policy() { return $this->belongsTo(IncrementPolicy::class); }
    public function appeal() { return $this->hasOne(IncrementAppeal::class, 'review_id'); }

    public function getStarRating(): int
    {
        $s = $this->avg_score;
        if ($s >= 90) return 5;
        if ($s >= 75) return 4;
        if ($s >= 60) return 3;
        if ($s >= 45) return 2;
        return 1;
    }

    public function getScoreLabel(): string
    {
        $s = $this->avg_score;
        if ($s >= 90) return 'Outstanding';
        if ($s >= 75) return 'Excellent';
        if ($s >= 60) return 'Good';
        if ($s >= 45) return 'Satisfactory';
        return 'Needs Improvement';
    }

    public function isOverridden(): bool { return !is_null($this->override_reason); }
}
