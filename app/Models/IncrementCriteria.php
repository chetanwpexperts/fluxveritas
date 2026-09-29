<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncrementCriteria extends Model
{
    protected $table = 'increment_criteria';

    protected $fillable = [
        'policy_id', 'organization_id', 'department_id',
        'criteria_name', 'criteria_label', 'criteria_type',
        'data_source', 'weight_percent', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'weight_percent' => 'float'];

    public function policy() { return $this->belongsTo(IncrementPolicy::class); }
    public function department() { return $this->belongsTo(Department::class); }
}
