<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'organization_id',
        'from_date', 'to_date', 'days', 'reason',
        'status', 'reviewed_by', 'reviewer_note', 'reviewed_at',
        'is_half_day', 'half_day_period',
    ];

    protected $casts = [
        'from_date'   => 'date',
        'to_date'     => 'date',
        'reviewed_at' => 'datetime',
        'is_half_day' => 'boolean',
        'days'        => 'decimal:1',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public static function calculateWorkingDays(string $from, string $to, bool $isHalfDay = false): float
    {
        if ($isHalfDay) {
            return 0.5;
        }

        $period = CarbonPeriod::create($from, $to);
        $days   = 0;

        foreach ($period as $date) {
            if (!$date->isWeekend()) {
                $days++;
            }
        }

        return (float) $days;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved'  => 'green',
            'rejected'  => 'red',
            'cancelled' => 'gray',
            default     => 'yellow',
        };
    }

    public function getTotalDaysAttribute(): float
    {
        return (float)$this->days;
    }

    public function getHalfDayAttribute(): string
    {
        if ($this->is_half_day && $this->half_day_period) {
            return $this->half_day_period;
        }
        return 'none';
    }

    public function getRejectionReasonAttribute(): ?string
    {
        return $this->status === 'rejected' ? $this->reviewer_note : null;
    }
}
