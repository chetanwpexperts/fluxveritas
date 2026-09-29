{{--
    Pro checkout: period toggle, price breakdown, pay button.
    Needs: $quotes (from BillingService::quote, keyed monthly|yearly), $period, $verb ('upgrade'|'extend').
    Optional: $afterPayUrl — where to go once paid (defaults to Billing).
    Requires public/js/billing.js and Razorpay's checkout.js on the page.
--}}
@php
    $inr     = fn (int $p) => \App\Services\BillingService::inr($p);
    $saving  = \App\Services\BillingService::yearlySavingPercent();
    $minSeat = (int) config('plans.pro.min_seats');
    $uid     = $uid ?? 'co';
@endphp

<div data-checkout
     data-order-url="{{ route('billing.order') }}"
     data-verify-url="{{ route('billing.verify') }}"
     data-failed-url="{{ route('billing.failed') }}"
     data-after-pay-url="{{ $afterPayUrl ?? route('billing.index') }}">

    <div class="bl-toggle" role="radiogroup" aria-label="Billing period">
        <input type="radio" name="period" id="{{ $uid }}-monthly" value="monthly" @checked($period === 'monthly')>
        <label for="{{ $uid }}-monthly">Monthly</label>
        <input type="radio" name="period" id="{{ $uid }}-yearly" value="yearly" @checked($period === 'yearly')>
        <label for="{{ $uid }}-yearly">Yearly @if($saving > 0)<span class="bl-save">Save {{ $saving }}%</span>@endif</label>
    </div>

    @foreach($quotes as $key => $q)
    <div class="bl-summary" style="margin-top:12px" data-period-panel="{{ $key }}" @if($key !== $period) hidden @endif>
        <div class="bl-row">
            <span>{{ $q['seats'] }} users × {{ $inr($q['unit_price']) }} × {{ $q['months'] }} {{ \Illuminate\Support\Str::plural('month', $q['months']) }}</span>
            <span class="bl-num">{{ $inr($q['subtotal']) }}</span>
        </div>
        @if($q['gst_percent'] > 0)
        <div class="bl-row">
            <span>GST ({{ rtrim(rtrim(number_format($q['gst_percent'], 2), '0'), '.') }}%)</span>
            <span class="bl-num">{{ $inr($q['tax']) }}</span>
        </div>
        @endif
        <div class="bl-row bl-row-total">
            <span>Total today</span>
            <span class="bl-num">{{ $inr($q['total']) }}</span>
        </div>
        <p class="bl-note">
            @if($q['seats'] > $q['users'])
                You have {{ $q['users'] }} {{ \Illuminate\Support\Str::plural('person', $q['users']) }}; Pro is billed for a minimum of {{ $minSeat }} users.
            @else
                Billed for the {{ $q['users'] }} active people in your organization.
            @endif
        </p>
        <div class="bl-period-dates">
            Pro {{ $q['is_renewal'] ? 'extended' : 'active' }}: {{ $q['starts_at']->format('j M Y') }} → {{ $q['ends_at']->format('j M Y') }}
        </div>
    </div>
    @endforeach

    <button type="button" class="bl-btn bl-btn-primary bl-pay" data-pay
            @foreach($quotes as $key => $q)
            data-label-{{ $key }}="Pay {{ $inr($q['total']) }} and {{ $verb }}"
            @endforeach>
        Pay {{ $inr($quotes[$period]['total']) }} and {{ $verb }}
    </button>

    <div class="bl-error" data-checkout-error role="alert"></div>

    <p class="bl-fineprint">
        Secure payment by Razorpay (UPI, cards, net banking).<br>
        Plans don’t renew automatically. Cancel within {{ config('plans.refund_window_days') }} days for a full refund.
    </p>
</div>
