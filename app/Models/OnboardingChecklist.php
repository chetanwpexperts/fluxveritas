<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingChecklist extends Model
{
    protected $fillable = [
        'organization_id', 'user_id', 'created_by', 'due_date', 'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function tasks()
    {
        return $this->hasMany(OnboardingTask::class, 'checklist_id')->orderBy('sort_order');
    }

    public function getCompletionPercentAttribute(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;
        $done = $this->tasks()->where('is_completed', true)->count();
        return (int) round(($done / $total) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && is_null($this->completed_at);
    }
}
