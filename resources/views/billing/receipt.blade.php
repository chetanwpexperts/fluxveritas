@php
    $inr     = fn (int $p) => \App\Services\BillingService::inr($p);
    $isTaxInvoice = !empty($seller['gstin']);
    $docTitle = $isTaxInvoice ? 'Tax Invoice' : 'Payment Receipt';
    $subtotal = $payment->subtotal ?? $payment->amount;
    $tax      = $payment->tax_amount ?? 0;
    $gstPct   = $subtotal > 0 && $tax > 0 ? round($tax / $subtotal * 100, 2) : 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docTitle }} {{ $payment->receipt_number }} — OutraqHQ</title>
    <link rel="stylesheet" href="{{ asset('css/billing.css') }}">
</head>
<body class="rc-body">

<div class="rc-actions">
    <a href="{{ route('billing.index') }}" class="bl-btn bl-btn-secondary bl-btn-sm">← Back to billing</a>
    <button type="button" class="bl-btn bl-btn-primary bl-btn-sm" onclick="window.print()">Download / print</button>
</div>

<main class="rc-sheet">
    <div class="rc-top">
        <div>
            <div class="rc-brand">{{ $seller['name'] }}</div>
            <div class="rc-meta">
                @if($seller['address']){!! nl2br(e($seller['address'])) !!}<br>@endif
                @if($seller['gstin'])GSTIN: {{ $seller['gstin'] }}<br>@endif
                @if($seller['email']){{ $seller['email'] }}@endif
            </div>
        </div>
        <div class="rc-doc">
            <h1>{{ $docTitle }}</h1>
            <div class="rc-meta">
                No. {{ $payment->receipt_number ?? ('#' . $payment->id) }}<br>
                Date: {{ ($payment->paid_at ?? $payment->created_at)->format('j M Y') }}<br>
                Payment ID: {{ $payment->razorpay_payment_id }}
            </div>
        </div>
    </div>

    <div class="rc-parties">
        <div>
            <h2>Billed to</h2>
            <strong>{{ $org->name }}</strong><br>
            @if($payment->paidBy){{ $payment->paidBy->name }} · {{ $payment->paidBy->email }}@endif
        </div>
        <div>
            <h2>Service period</h2>
            @if($payment->period_start && $payment->period_end)
                {{ $payment->period_start->format('j M Y') }} – {{ $payment->period_end->format('j M Y') }}
            @else
                —
            @endif
        </div>
    </div>

    <table class="rc-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="rc-r">Qty</th>
                <th class="rc-r">Rate</th>
                <th class="rc-r">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    OutraqHQ {{ ucfirst($payment->plan) }} plan — {{ ucfirst($payment->billing_period) }}
                    @if($isTaxInvoice && $seller['sac'])<div class="rc-meta">SAC {{ $seller['sac'] }}</div>@endif
                </td>
                <td class="rc-r">{{ $payment->seats ?? 1 }} users</td>
                <td class="rc-r">
                    @if($payment->unit_price)
                        {{ $inr($payment->unit_price) }}/user/mo
                    @else
                        —
                    @endif
                </td>
                <td class="rc-r">{{ $inr($subtotal) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="rc-totals">
        <div><span>Subtotal</span><span>{{ $inr($subtotal) }}</span></div>
        @if($tax > 0)
            <div><span>GST ({{ rtrim(rtrim(number_format($gstPct, 2), '0'), '.') }}%)</span><span>{{ $inr($tax) }}</span></div>
        @endif
        <div class="rc-total"><span>Total paid</span><span>{{ $inr($payment->amount) }}</span></div>
    </div>

    @if($payment->isRefunded())
        <div class="rc-refund">
            Refunded {{ $inr($payment->refund_amount ?? $payment->amount) }} on {{ $payment->refunded_at?->format('j M Y') }}
            @if($payment->razorpay_refund_id)(Refund ID {{ $payment->razorpay_refund_id }})@endif
        </div>
    @endif

    <p class="rc-foot">
        Paid online via Razorpay in INR. This is a computer-generated document and does not need a signature.
    </p>
</main>

</body>
</html>
