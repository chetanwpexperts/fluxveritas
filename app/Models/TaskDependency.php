<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    protected $fillable = [
        'organization_id',
        'task_id',
        'depends_on_task_id',
        'depends_on_user_id',
        'dependency_type',
        'status',
        'notes',
        'unblocked_at',
    ];

    protected function casts(): array
    {
        return [
            'unblocked_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    public function dependsOnUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'depends_on_user_id');
    }

    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'waiting');
    }
}
