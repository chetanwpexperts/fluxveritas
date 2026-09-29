<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'assigned_to',
        'asset_name',
        'category',
        'serial_number',
        'asset_condition',
        'status',
        'assigned_at',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
