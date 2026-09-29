<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentRule extends Model
{
    protected $fillable = [
        'organization_id',
        'rule_name',
        'rule_label',
        'description',
        'is_enabled',
        'trigger_condition',
        'trigger_value',
        'notify_user',
        'notify_manager',
        'notify_owner',
        'cooldown_hours',
        'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'        => 'boolean',
            'notify_user'       => 'boolean',
            'notify_manager'    => 'boolean',
            'notify_owner'      => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
