<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentIntent extends Model
{
    protected $fillable = [
        'intent_name',
        'display_name',
        'description',
        'triggers',
        'is_active',
    ];

    protected $casts = [
        'triggers'  => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
