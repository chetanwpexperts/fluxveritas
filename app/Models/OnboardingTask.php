<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingTask extends Model
{
    protected $fillable = [
        'checklist_id', 'title', 'description', 'assigned_to_user',
        'due_date', 'is_completed', 'completed_at', 'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'due_date'     => 'date',
    ];

    public function checklist()
    {
        return $this->belongsTo(OnboardingChecklist::class, 'checklist_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user');
    }

    public static function defaultTasks(): array
    {
        return [
            'Send welcome email to new employee',
            'Create system accounts (email, Slack, GitHub)',
            'Assign to department and team in OutraqHQ',
            'Share company handbook',
            'Schedule first day orientation',
            'Set up workstation / access cards',
            'Introduce to reporting manager',
            'Complete profile in OutraqHQ',
        ];
    }
}
