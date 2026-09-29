<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'project_id', 'assigned_to', 'assigned_by', 'title', 'description',
        'difficulty', 'visibility_score', 'status', 'started_at', 'completed_at',
        'blocked_reason', 'ticket_number', 'type', 'priority', 'label',
        'department_id', 'sprint_id', 'parent_task_id', 'reporter_id', 'due_date',
        'estimated_hours', 'actual_hours', 'watchers', 'attachments', 'order_index',
        'completed_by', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'difficulty'      => 'integer',
            'visibility_score'=> 'integer',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
            'archived_at'     => 'datetime',
            'due_date'        => 'date',
            'watchers'        => 'array',
            'attachments'     => 'array',
            'estimated_hours' => 'decimal:2',
            'actual_hours'    => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($task) {
            if (!$task->ticket_number) {
                $project = Project::find($task->project_id);
                if ($project) {
                    $org = Organization::find($project->organization_id);
                    if ($org) {
                        $count = Task::whereHas('project', fn($q) => $q->where('organization_id', $org->id))->count() + 1;
                        $prefix = strtoupper(substr($org->slug ?? $org->name, 0, 3));
                        $task->ticket_number = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                    }
                }
            }
            if (!$task->reporter_id && auth()->check()) {
                $task->reporter_id = auth()->id();
            }
        });
    }

    public function project(): BelongsTo     { return $this->belongsTo(Project::class); }
    public function assignee(): BelongsTo    { return $this->belongsTo(User::class, 'assigned_to'); }
    public function assigner(): BelongsTo    { return $this->belongsTo(User::class, 'assigned_by'); }
    public function reporter(): BelongsTo    { return $this->belongsTo(User::class, 'reporter_id'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
    public function sprint(): BelongsTo      { return $this->belongsTo(Sprint::class); }
    public function department(): BelongsTo  { return $this->belongsTo(Department::class); }
    public function parent(): BelongsTo      { return $this->belongsTo(Task::class, 'parent_task_id'); }
    public function subtasks(): HasMany      { return $this->hasMany(Task::class, 'parent_task_id'); }
    public function workLogs(): HasMany      { return $this->hasMany(WorkLog::class); }
    public function comments(): HasMany      { return $this->hasMany(TaskComment::class); }
    public function history(): HasMany       { return $this->hasMany(TaskHistory::class); }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'backlog'     => '#a1a1aa',
            'todo'        => '#71717a',
            'in_progress' => '#3b82f6',
            'in_review'   => '#8b5cf6',
            'blocked'     => '#ef4444',
            'done'        => '#10b981',
            'cancelled'   => '#d4d4d8',
            default       => '#71717a',
        };
    }

    public function getPriorityIcon(): string
    {
        return match($this->priority) {
            'critical' => '🔴',
            'high'     => '🟠',
            'medium'   => '🟡',
            'low'      => '🟢',
            default    => '⚪',
        };
    }

    public function getTypeIcon(): string
    {
        return match($this->type) {
            'bug'         => '🐛',
            'feature'     => '✨',
            'improvement' => '📈',
            'story'       => '📖',
            'epic'        => '⚡',
            'question'    => '❓',
            'incident'    => '🚨',
            default       => '📋',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'backlog'     => 'Backlog',
            'todo'        => 'To Do',
            'in_progress' => 'In Progress',
            'in_review'   => 'In Review',
            'blocked'     => 'Blocked',
            'done'        => 'Done',
            'cancelled'   => 'Cancelled',
            default       => ucfirst($this->status),
        };
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['done', 'cancelled']);
    }

    public function totalLoggedHours(): float
    {
        return round($this->workLogs()->sum('duration_minutes') / 60, 1);
    }

    public function hoursProgress(): float
    {
        if (!$this->estimated_hours || $this->estimated_hours == 0) return 0;
        return min(100, round(($this->actual_hours / $this->estimated_hours) * 100));
    }

    public function isOverEstimate(): bool
    {
        return $this->estimated_hours > 0 && $this->actual_hours > $this->estimated_hours;
    }
}
