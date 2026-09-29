<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Smart Import run (see App\Services\Import). */
class EmployeeImport extends Model
{
    protected $fillable = [
        'organization_id', 'user_id', 'original_name', 'file_path', 'preset', 'headers', 'mapping', 'options',
        'status', 'total_rows', 'processed_rows', 'created_count', 'updated_count', 'skipped_count', 'error_count',
        'summary', 'errors_path', 'failure_message', 'started_at', 'finished_at', 'invites_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'headers'         => 'array',
            'mapping'         => 'array',
            'options'         => 'array',
            'summary'         => 'array',
            'started_at'      => 'datetime',
            'finished_at'     => 'datetime',
            'invites_sent_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }

    public function progressPercent(): int
    {
        return $this->total_rows > 0 ? (int) min(100, round($this->processed_rows / $this->total_rows * 100)) : 0;
    }
}
