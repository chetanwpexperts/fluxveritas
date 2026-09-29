<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationModule extends Model
{
    protected $fillable = [
        'organization_id',
        'module_name',
        'is_enabled',
        'enabled_at',
        'disabled_at',
        'enabled_by',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'  => 'boolean',
            'enabled_at'  => 'datetime',
            'disabled_at' => 'datetime',
            'settings'    => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }
}
