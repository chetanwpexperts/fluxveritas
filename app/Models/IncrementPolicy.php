<?php
namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;

class IncrementPolicy extends Model
{
    use HasOrganizationScope;
    protected $fillable = [
        'organization_id', 'name', 'max_increment_percent', 'review_period',
        'review_month', 'minimum_months_required', 'minimum_score_for_increment',
        'anti_gaming_enabled', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'anti_gaming_enabled' => 'boolean',
        'max_increment_percent' => 'float',
        'minimum_score_for_increment' => 'float',
    ];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function criteria() { return $this->hasMany(IncrementCriteria::class, 'policy_id'); }
    public function reviews() { return $this->hasMany(IncrementReview::class, 'policy_id'); }
}
