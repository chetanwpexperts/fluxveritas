<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'plan_expires_at',
        'billing_status',
        'billing_period',
        'seats',
        'downgrade_scheduled_at',
        'status',
        'max_employees',
        'settings',
        'approved_at',
        'approved_by',
        'owner_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'settings'        => 'array',
            'max_employees'   => 'integer',
            'approved_at'     => 'datetime',
            'plan_expires_at' => 'datetime',
            'seats'                  => 'integer',
            'downgrade_scheduled_at' => 'datetime',
        ];
    }

    /**
     * The plan the organization can actually use right now. A paid plan whose
     * period has ended counts as free even before the nightly expiry job runs.
     */
    public function effectivePlan(): string
    {
        $plan = $this->plan ?: 'free';

        if ($plan !== 'free' && $this->plan_expires_at && $this->plan_expires_at->isPast()) {
            return 'free';
        }

        return $plan;
    }

    public function isOnPaidPlan(): bool
    {
        return $this->effectivePlan() !== 'free';
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function fairnessFlags(): HasMany
    {
        return $this->hasMany(FairnessFlag::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
