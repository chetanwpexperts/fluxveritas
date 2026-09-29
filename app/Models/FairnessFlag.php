<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FairnessFlag extends Model
{
    use HasOrganizationScope;
    protected $fillable = [
        'organization_id',
        'flagged_user_id',
        'flagged_by_user_id',
        'flag_type',
        'confidence_score',
        'layer',
        'status',
        'evidence',
        'employee_response',
        'responded_at',
        'reviewed_by',
        'reviewed_at',
        'resolution',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'confidence_score' => 'decimal:4',
            'layer' => 'integer',
            'responded_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function flaggedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_user_id');
    }

    public function flaggedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
