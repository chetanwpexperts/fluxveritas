<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** An Outy action waiting for the user to press Confirm. */
class OutyPendingAction extends Model
{
    use MassPrunable;

    public const TTL_MINUTES = 10;

    protected $fillable = [
        'token', 'user_id', 'organization_id', 'tool', 'payload', 'summary',
        'status', 'result', 'expires_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'summary'     => 'array',
            'expires_at'  => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Old actions are removed by `php artisan model:prune` (scheduled daily). */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(7));
    }
}
