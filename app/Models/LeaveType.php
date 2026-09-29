<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'code', 'description',
        'days_per_year', 'is_paid', 'carry_forward',
        'max_carry_forward', 'requires_approval', 'is_active',
    ];

    protected $casts = [
        'is_paid'           => 'boolean',
        'carry_forward'     => 'boolean',
        'requires_approval' => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function applications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }
}
