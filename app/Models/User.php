<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'role',
        'github_username',
        'department',
        'join_date',
        'is_active',
        'settings',
        'onboarding_status',
        'onboarding_type',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'department_id',
        'job_title',
        'designation',
        'seniority_level',
        'employment_type',
        'team_id',
        'reporting_manager_id',
        'work_location',
        'skills',
        'profile_photo',
        'probation_end_date',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'join_date'          => 'date',
            'is_active'          => 'boolean',
            'settings'           => 'array',
            'approved_at'        => 'datetime',
            'skills'             => 'array',
            'probation_end_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function fairnessFlags(): HasMany
    {
        return $this->hasMany(FairnessFlag::class, 'flagged_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    public function employeeStatuses(): HasMany
    {
        return $this->hasMany(EmployeeStatus::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'reporting_manager_id');
    }

    public function manageableUserIds(): \Illuminate\Support\Collection
    {
        $orgId = $this->organization_id;

        if ($this->hasAnyRole(['super_admin', 'owner', 'admin'])) {
            return static::where('organization_id', $orgId)->pluck('id');
        }

        if ($this->hasRole('manager')) {
            $direct = static::where('organization_id', $orgId)
                ->where('reporting_manager_id', $this->id)
                ->pluck('id');

            $second = static::where('organization_id', $orgId)
                ->whereIn('reporting_manager_id', $direct)
                ->pluck('id');

            return $direct->merge($second)->push($this->id)->unique()->values();
        }

        if ($this->hasRole('team_lead')) {
            return static::where('organization_id', $orgId)
                ->where('reporting_manager_id', $this->id)
                ->pluck('id')
                ->push($this->id)
                ->unique()
                ->values();
        }

        return collect([$this->id]);
    }

    public function designationModel(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation', 'slug');
    }

    public function getFullTitleAttribute(): string
    {
        $parts = array_filter([
            $this->seniority_level ? ucfirst($this->seniority_level) : null,
            $this->job_title,
        ]);
        return implode(' ', $parts) ?: ($this->role ?? 'Employee');
    }

    public function isOnProbation(): bool
    {
        return $this->employment_type === 'probation'
            && $this->probation_end_date
            && $this->probation_end_date->isFuture();
    }

    public function requiresGithub(): bool
    {
        $techDesignations = ['developer', 'devops', 'architect', 'tech_lead', 'automation_engineer'];
        return in_array($this->designation, $techDesignations);
    }

    public function managedFeedbacks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PerformanceFeedback::class, 'manager_id');
    }

    public function receivedFeedbacks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PerformanceFeedback::class, 'employee_id');
    }
}
