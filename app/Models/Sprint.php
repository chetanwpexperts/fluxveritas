<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use HasOrganizationScope;
    protected $fillable = [
        'organization_id', 'project_id', 'name', 'goal', 'status',
        'start_date', 'end_date', 'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function project(): BelongsTo      { return $this->belongsTo(Project::class); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function tasks(): HasMany          { return $this->hasMany(Task::class); }
}
