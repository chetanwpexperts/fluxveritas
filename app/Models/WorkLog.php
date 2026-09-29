<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLog extends Model
{
    use HasOrganizationScope;

    protected static function booted(): void
    {
        static::created(function (WorkLog $log) {
            if ($log->task_id && $log->duration_minutes) {
                $hours = round($log->duration_minutes / 60, 2);
                Task::where('id', $log->task_id)->increment('actual_hours', $hours);
            }
        });

        static::updated(function (WorkLog $log) {
            if ($log->task_id) {
                $totalMinutes = WorkLog::where('task_id', $log->task_id)->sum('duration_minutes');
                Task::where('id', $log->task_id)->update(['actual_hours' => round($totalMinutes / 60, 2)]);
            }
        });

        static::deleted(function (WorkLog $log) {
            if ($log->task_id) {
                $totalMinutes = WorkLog::where('task_id', $log->task_id)->sum('duration_minutes');
                Task::where('id', $log->task_id)->update(['actual_hours' => round($totalMinutes / 60, 2)]);
            }
        });
    }
    protected $fillable = [
        'user_id',
        'organization_id',
        'department_id',
        'project_id',
        'task_id',
        'log_date',
        'category',
        'title',
        'description',
        'duration_minutes',
        'output_value',
        'is_billable',
        'tags',
        'evidence',
    ];

    protected function casts(): array
    {
        return [
            'tags'        => 'array',
            'log_date'    => 'date',
            'is_billable' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getDurationFormatted(): string
    {
        if (!$this->duration_minutes) return 'N/A';
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'meeting'       => 'Meeting',
            'code_review'   => 'Code Review',
            'development'   => 'Development',
            'research'      => 'Research',
            'support'       => 'Support',
            'training'      => 'Training',
            'travel'        => 'Travel',
            'client_call'   => 'Client Call',
            'vendor_call'   => 'Vendor Call',
            'planning'      => 'Planning',
            'documentation' => 'Documentation',
            'design'        => 'Design',
            'testing'       => 'Testing',
            'reporting'     => 'Reporting',
            'recruitment'   => 'Recruitment',
            default         => 'Other',
        };
    }

    public function getCategoryColor(): string
    {
        return match($this->category) {
            'meeting'       => '#3b82f6',
            'code_review'   => '#8b5cf6',
            'development'   => '#10b981',
            'research'      => '#f59e0b',
            'support'       => '#ef4444',
            'training'      => '#06b6d4',
            'travel'        => '#f97316',
            'client_call'   => '#84cc16',
            'vendor_call'   => '#ec4899',
            'planning'      => '#6366f1',
            'documentation' => '#14b8a6',
            'design'        => '#a855f7',
            'testing'       => '#0ea5e9',
            'reporting'     => '#64748b',
            'recruitment'   => '#d946ef',
            default         => '#71717a',
        };
    }
}
