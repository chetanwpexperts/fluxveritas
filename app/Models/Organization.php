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
        ];
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
