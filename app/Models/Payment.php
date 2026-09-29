<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'organization_id',
        'paid_by',
        'plan',
        'billing_period',
        'seats',
        'unit_price',
        'subtotal',
        'tax_amount',
        'amount',
        'currency',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'razorpay_refund_id',
        'refund_status',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'refunded_by',
        'status',
        'paid_at',
        'period_start',
        'period_end',
        'receipt_number',
        'failure_reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'paid_at'      => 'datetime',
            'period_start' => 'datetime',
            'period_end'   => 'datetime',
            'refunded_at'  => 'datetime',
            'meta'         => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /** Short line for history tables, e.g. "Pro · Yearly · 12 users". */
    public function description(): string
    {
        $parts = [ucfirst($this->plan), ucfirst($this->billing_period)];
        if ($this->seats) {
            $parts[] = $this->seats . ' ' . ($this->seats === 1 ? 'user' : 'users');
        }
        return implode(' · ', $parts);
    }

    /** Human-readable status for the history table. */
    public function statusLabel(): string
    {
        if ($this->isRefunded()) {
            return match ($this->refund_status) {
                'processed' => 'Refunded',
                'failed'    => 'Refund failed',
                default     => 'Refund in progress',
            };
        }

        return match ($this->status) {
            'paid'   => 'Paid',
            'failed' => 'Failed',
            default  => 'Not completed',
        };
    }
}
