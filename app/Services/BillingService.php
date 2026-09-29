<?php

namespace App\Services;

use App\Exceptions\BillingException;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\TeamInvitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

/**
 * All plan and payment logic. Prices come from config/plans.php.
 *
 * Plans are prepaid (monthly or yearly, no auto-renew). A payment adds one
 * period to the organization's plan; refunding it removes that period again.
 */
class BillingService
{
    public function __construct(private ?Api $api = null) {}

    public function api(): Api
    {
        return $this->api ??= new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    // ── Pricing ──────────────────────────────────────────────────────────────

    /** Formats paise as rupees with Indian digit grouping, e.g. 12345600 → ₹1,23,456. */
    public static function inr(int $paise): string
    {
        $rupees   = (string) intdiv($paise, 100);
        $fraction = $paise % 100;

        if (strlen($rupees) > 3) {
            $rest   = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($rupees, 0, -3));
            $rupees = $rest . ',' . substr($rupees, -3);
        }

        return '₹' . $rupees . ($fraction ? '.' . str_pad((string) $fraction, 2, '0', STR_PAD_LEFT) : '');
    }

    /** Percentage saved by paying yearly instead of monthly. */
    public static function yearlySavingPercent(): int
    {
        $monthly = (int) config('plans.pro.periods.monthly.price_per_user');
        $yearly  = (int) config('plans.pro.periods.yearly.price_per_user');

        return $monthly > 0 ? (int) round((1 - $yearly / $monthly) * 100) : 0;
    }

    public function isValidPeriod(string $period): bool
    {
        return array_key_exists($period, config('plans.pro.periods', []));
    }

    public function activeUserCount(Organization $org): int
    {
        return User::where('organization_id', $org->id)->where('is_active', true)->count();
    }

    /** When a new period would start: after the current paid period, or now. */
    public function nextPeriodStart(Organization $org): Carbon
    {
        if ($org->effectivePlan() === 'pro' && $org->plan_expires_at?->isFuture()) {
            return $org->plan_expires_at->copy();
        }

        return now();
    }

    /**
     * Price breakdown for buying Pro for one period. All amounts in paise.
     */
    public function quote(Organization $org, string $period): array
    {
        if (!$this->isValidPeriod($period)) {
            throw new BillingException('Unknown billing period.');
        }

        $cfg      = config("plans.pro.periods.$period");
        $users    = $this->activeUserCount($org);
        $seats    = max((int) config('plans.pro.min_seats'), $users);
        $months   = (int) $cfg['months'];
        $unit     = (int) $cfg['price_per_user'];
        $subtotal = $unit * $seats * $months;
        $gst      = (float) config('plans.gst_percent');
        $tax      = (int) round($subtotal * $gst / 100);
        $start    = $this->nextPeriodStart($org);

        return [
            'plan'        => 'pro',
            'period'      => $period,
            'months'      => $months,
            'users'       => $users,
            'seats'       => $seats,
            'unit_price'  => $unit,
            'subtotal'    => $subtotal,
            'gst_percent' => $gst,
            'tax'         => $tax,
            'total'       => $subtotal + $tax,
            'starts_at'   => $start,
            'ends_at'     => $start->copy()->addMonthsNoOverflow($months),
            'is_renewal'  => $start->isFuture(),
        ];
    }

    // ── Checkout ─────────────────────────────────────────────────────────────

    public function createOrder(Organization $org, User $user, string $period): Payment
    {
        if ($org->effectivePlan() === 'enterprise') {
            throw new BillingException('Your organization is on Enterprise. Contact us to change your plan.');
        }

        $q = $this->quote($org, $period);

        $order = $this->api()->order->create([
            'amount'   => $q['total'],
            'currency' => 'INR',
            'receipt'  => 'org_' . $org->id . '_' . now()->timestamp,
            'notes'    => [
                'organization_id' => $org->id,
                'plan'            => 'pro',
                'period'          => $period,
                'seats'           => $q['seats'],
            ],
        ]);

        return Payment::create([
            'organization_id'   => $org->id,
            'paid_by'           => $user->id,
            'plan'              => 'pro',
            'billing_period'    => $period,
            'seats'             => $q['seats'],
            'unit_price'        => $q['unit_price'],
            'subtotal'          => $q['subtotal'],
            'tax_amount'        => $q['tax'],
            'amount'            => $q['total'],
            'currency'          => 'INR',
            'razorpay_order_id' => $order['id'],
            'status'            => 'created',
        ]);
    }

    /**
     * Confirms with Razorpay that the payment belongs to this order and is for
     * the right amount, and captures it if it was only authorized.
     */
    public function confirmWithGateway(Payment $payment, string $razorpayPaymentId): void
    {
        $remote = $this->api()->payment->fetch($razorpayPaymentId);

        if ($remote['order_id'] !== $payment->razorpay_order_id || (int) $remote['amount'] !== (int) $payment->amount) {
            Log::warning('Billing: payment does not match order', [
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $razorpayPaymentId,
            ]);
            throw new BillingException('This payment does not match your order. Please contact support.');
        }

        if ($remote['status'] === 'authorized') {
            $remote->capture(['amount' => $payment->amount, 'currency' => $payment->currency]);
        }
    }

    /**
     * Marks a payment as paid and activates the plan. Safe to call more than
     * once (browser verify and webhook can both arrive).
     */
    public function markPaid(Payment $payment, string $razorpayPaymentId, ?string $signature = null): Payment
    {
        return DB::transaction(function () use ($payment, $razorpayPaymentId, $signature) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (in_array($payment->status, ['paid', 'refunded'], true)) {
                return $payment;
            }

            $org    = Organization::whereKey($payment->organization_id)->lockForUpdate()->firstOrFail();
            $months = (int) config("plans.pro.periods.{$payment->billing_period}.months", 1);
            $start  = $this->nextPeriodStart($org);
            $end    = $start->copy()->addMonthsNoOverflow($months);

            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature'  => $signature ?? $payment->razorpay_signature,
                'status'              => 'paid',
                'paid_at'             => now(),
                'period_start'        => $start,
                'period_end'          => $end,
                'receipt_number'      => 'OQ-' . now()->format('Y') . '-' . str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
                'failure_reason'      => null,
            ]);

            $org->update([
                'plan'                   => $payment->plan,
                'plan_expires_at'        => $end,
                'billing_status'         => 'active',
                'billing_period'         => $payment->billing_period,
                'seats'                  => $payment->seats,
                'downgrade_scheduled_at' => null,
            ]);

            Log::info('Billing: plan activated', [
                'org_id' => $org->id, 'payment_id' => $payment->id, 'until' => $end->toDateTimeString(),
            ]);

            return $payment;
        });
    }

    public function markFailed(Payment $payment, ?string $reason): void
    {
        if ($payment->status !== 'created') {
            return;
        }

        $payment->update([
            'status'         => 'failed',
            'failure_reason' => $reason ? Str::limit($reason, 250) : null,
        ]);
    }

    // ── Refunds ──────────────────────────────────────────────────────────────

    public function refundWindowDays(): int
    {
        return (int) config('plans.refund_window_days', 7);
    }

    /** The most recent payment that can still be refunded, if any. */
    public function refundablePayment(Organization $org): ?Payment
    {
        $payment = Payment::where('organization_id', $org->id)
            ->where('status', 'paid')
            ->whereNotNull('razorpay_payment_id')
            ->where('paid_at', '>=', now()->subDays($this->refundWindowDays()))
            ->latest('paid_at')
            ->first();

        return $payment && $this->refundBlockReason($payment) === null ? $payment : null;
    }

    /** Why a payment cannot be refunded, or null if it can. */
    public function refundBlockReason(Payment $payment): ?string
    {
        if (!$payment->isPaid() || !$payment->razorpay_payment_id) {
            return 'Only completed payments can be refunded.';
        }

        $days = $this->refundWindowDays();
        if ($payment->paid_at->lt(now()->subDays($days))) {
            return "Refunds are available within {$days} days of payment. This payment was made on "
                . $payment->paid_at->format('j M Y') . '.';
        }

        $limit = config('plans.refund_limit_per_org');
        if ($limit !== null) {
            $used = Payment::where('organization_id', $payment->organization_id)->where('status', 'refunded')->count();
            if ($used >= $limit) {
                return 'Your organization has already used its refund. Please contact support.';
            }
        }

        return null;
    }

    /**
     * Refunds the full payment through Razorpay and removes the period it paid for.
     */
    public function refund(Payment $payment, User $by, ?string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $by, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($block = $this->refundBlockReason($payment)) {
                throw new BillingException($block);
            }

            try {
                $refund = $this->api()->payment->fetch($payment->razorpay_payment_id)->refund([
                    'amount'  => $payment->amount,
                    'speed'   => 'normal',
                    'receipt' => 'refund_' . $payment->id,
                    'notes'   => [
                        'organization_id' => $payment->organization_id,
                        'reason'          => Str::limit((string) $reason, 200),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Billing: refund failed at gateway', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                throw new BillingException('We could not start the refund with our payment provider. No changes were made — please try again or contact support.');
            }

            $payment->update([
                'status'             => 'refunded',
                'razorpay_refund_id' => $refund['id'],
                'refund_status'      => $refund['status'] ?? 'pending',
                'refund_amount'      => $payment->amount,
                'refund_reason'      => $reason ? Str::limit($reason, 1000) : null,
                'refunded_at'        => now(),
                'refunded_by'        => $by->id,
            ]);

            $this->removePaidPeriod($payment);

            return $payment;
        });
    }

    /**
     * When the plan would end if this payment were refunded, or null if the
     * organization would drop straight to Free.
     */
    public function expiryAfterRefund(Payment $payment, ?Organization $org = null): ?Carbon
    {
        $org   ??= $payment->organization;
        $start   = $payment->period_start ?? $payment->paid_at;
        $end     = $payment->period_end ?? $payment->paid_at->copy()->addDays(30);
        $expiry  = $org->plan_expires_at?->copy()->subSeconds((int) abs($start->diffInSeconds($end)));

        return $expiry && $expiry->isFuture() ? $expiry : null;
    }

    /** Takes the refunded period off the plan; drops to Free if nothing paid remains. */
    private function removePaidPeriod(Payment $payment): void
    {
        $org       = Organization::whereKey($payment->organization_id)->lockForUpdate()->firstOrFail();
        $newExpiry = $this->expiryAfterRefund($payment, $org);

        if (!$newExpiry) {
            $this->resetToFree($org);
            return;
        }

        $org->update(['plan_expires_at' => $newExpiry]);
    }

    public function applyRefundStatus(string $razorpayRefundId, string $status): void
    {
        Payment::where('razorpay_refund_id', $razorpayRefundId)->update(['refund_status' => $status]);
    }

    // ── Plan changes ─────────────────────────────────────────────────────────

    /**
     * Keep the paid plan until the period ends, then move to Free. A plan with
     * no end date (granted manually) moves to Free right away.
     */
    public function scheduleDowngrade(Organization $org): void
    {
        if ($org->effectivePlan() !== 'pro') {
            throw new BillingException('Only a Pro plan can be switched to Free.');
        }

        if (!$org->plan_expires_at) {
            $this->resetToFree($org);
            return;
        }

        $org->update(['downgrade_scheduled_at' => now()]);
    }

    public function cancelDowngrade(Organization $org): void
    {
        $org->update(['downgrade_scheduled_at' => null]);
    }

    public function resetToFree(Organization $org, string $status = 'free'): void
    {
        $org->update([
            'plan'                   => 'free',
            'billing_status'         => $status,
            'plan_expires_at'        => null,
            'billing_period'         => null,
            'seats'                  => null,
            'downgrade_scheduled_at' => null,
        ]);
    }

    // ── Free plan user limit ─────────────────────────────────────────────────

    public function freeUserLimit(): int
    {
        return (int) config('plans.free.max_users', 10);
    }

    /** Active users plus people with a pending invitation. */
    public function usedFreeSeats(Organization $org, array $excludeInviteEmails = []): int
    {
        $pending = TeamInvitation::where('organization_id', $org->id)
            ->pending()
            ->when($excludeInviteEmails, fn ($q) => $q->whereNotIn('email', $excludeInviteEmails))
            ->count();

        return $this->activeUserCount($org) + $pending;
    }

    /**
     * Error message if adding $adding people would exceed the Free plan limit,
     * or null if it is allowed. Paid plans have no limit.
     */
    public function seatLimitError(?Organization $org, int $adding = 1, array $excludeInviteEmails = []): ?string
    {
        if (!$org || $org->isOnPaidPlan()) {
            return null;
        }

        $limit = $this->freeUserLimit();
        $used  = $this->usedFreeSeats($org, $excludeInviteEmails);

        if ($used + $adding <= $limit) {
            return null;
        }

        $left = max(0, $limit - $used);

        return $left === 0
            ? "The Free plan includes up to {$limit} people (including pending invites), and your organization has reached that limit. Upgrade to Pro to add more."
            : "The Free plan includes up to {$limit} people (including pending invites). You can add {$left} more — upgrade to Pro to add everyone.";
    }
}
