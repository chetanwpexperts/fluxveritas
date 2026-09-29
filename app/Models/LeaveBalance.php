<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'organization_id',
        'year', 'allocated', 'used', 'pending', 'carried_forward',
    ];

    protected $casts = [
        'allocated'       => 'decimal:1',
        'used'            => 'decimal:1',
        'pending'         => 'decimal:1',
        'carried_forward' => 'decimal:1',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getAvailableAttribute(): float
    {
        return max(0, (float)$this->allocated + (float)$this->carried_forward - (float)$this->used - (float)$this->pending);
    }

    public function getTotalDaysAttribute(): float
    {
        return (float)$this->allocated + (float)$this->carried_forward;
    }

    public function getUsedDaysAttribute(): float
    {
        return (float)$this->used;
    }

    public function getPendingDaysAttribute(): float
    {
        return (float)$this->pending;
    }
}
