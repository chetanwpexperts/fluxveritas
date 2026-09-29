@extends('layouts.app')
@section('title', 'Billing')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}">
@endpush

@php
    $inr       = fn (int $p) => \App\Services\BillingService::inr($p);
    $expires   = $org->plan_expires_at;
    $isPro     = $plan === 'pro';
    $scheduled = $isPro && $org->downgrade_scheduled_at;
    $daysLeft  = ($isPro && $expires) ? (int) now()->startOfDay()->diffInDays($expires->copy()->startOfDay()) : null;
    $endingSoon = $isPro && !$scheduled && $daysLeft !== null && $daysLeft <= 7;
    $labels    = ['free' => 'Free', 'pro' => 'Pro', 'enterprise' => 'Enterprise'];
@endphp

@section('content')
<div class="bl-page">

    <div class="bl-header">
        <h1 class="bl-title">Billing</h1>
        <p class="bl-sub">Your plan, payments and receipts.</p>
    </div>

    {{-- ── Status banners ─────────────────────────────────────────────── --}}
    @if($scheduled)
        <div class="bl-banner bl-banner-info">
            <span>Pro stays active until <strong>{{ $expires->format('j M Y') }}</strong>, then your organization moves to the Free plan.</span>
            <form method="POST" action="{{ route('billing.downgrade.cancel') }}">
                @csrf
                <button type="submit" class="bl-btn bl-btn-secondary bl-btn-sm">Keep Pro</button>
            </form>
        </div>
    @elseif($endingSoon)
        <div class="bl-banner bl-banner-warn">
            <span>Pro ends on <strong>{{ $expires->format('j M Y') }}</strong>
                ({{ $daysLeft === 0 ? 'today' : 'in ' . $daysLeft . ' ' . \Illuminate\Support\Str::plural('day', $daysLeft) }}).
                Renew below to keep Pro features without interruption.</span>
        </div>
    @elseif($plan === 'free' && $org->billing_status === 'expired')
        <div class="bl-banner bl-banner-danger">
            <span>Your Pro plan has ended and your organization is on Free. All your data is kept — renew below to switch Pro features back on.</span>
        </div>
    @endif

    @if($plan === 'free' && $usedSeats >= $freeLimit)
        <div class="bl-banner bl-banner-warn">
            <span>You’re using {{ $usedSeats }} of {{ $freeLimit }} places on the Free plan (including pending invites). Upgrade to Pro to add more people.</span>
        </div>
    @endif

    {{-- ── Current plan ──────────────────────────────────────────────── --}}
    <section class="bl-card" aria-labelledby="current-plan">
        <div class="bl-plan">
            <div>
                <div class="bl-plan-label" id="current-plan">Current plan</div>
                <div class="bl-plan-name">
                    {{ $labels[$plan] ?? ucfirst($plan) }}
                    @if($plan === 'free')
                        <span class="bl-pill bl-pill-grey">Free forever</span>
                    @elseif($scheduled)
                        <span class="bl-pill bl-pill-blue">Switching to Free</span>
                    @elseif($endingSoon)
                        <span class="bl-pill bl-pill-amber">Ends {{ $daysLeft === 0 ? 'today' : 'in ' . $daysLeft . 'd' }}</span>
                    @else
                        <span class="bl-pill bl-pill-green">Active</span>
                    @endif
                </div>

                <dl class="bl-facts">
                    @if($plan === 'free')
                        <dt>Price</dt><dd>₹0</dd>
                        <dt>People</dt><dd>{{ $usedSeats }} of {{ $freeLimit }} <span class="bl-muted" style="display:inline">(incl. pending invites)</span></dd>
                    @elseif($isPro)
                        <dt>Billing cycle</dt><dd>{{ ucfirst($org->billing_period ?? 'monthly') }}</dd>
                        <dt>Paid for</dt><dd>{{ $org->seats ? $org->seats . ' users' : '—' }}</dd>
                        <dt>People now</dt><dd>{{ $activeUsers }}</dd>
                        <dt>{{ $scheduled ? 'Pro until' : 'Active until' }}</dt><dd>{{ $expires ? $expires->format('j M Y') : 'No end date' }}</dd>
                    @else
                        <dt>People now</dt><dd>{{ $activeUsers }}</dd>
                        <dt>Active until</dt><dd>{{ $expires ? $expires->format('j M Y') : 'Managed by your account team' }}</dd>
                    @endif
                </dl>
            </div>

            <div>
                <div class="bl-features-title">Included in your plan</div>
                <ul class="bl-features bl-features-cols">
                    @foreach($planFeatures as $feature)
                        <li><span class="bl-check" aria-hidden="true">✓</span>{{ $feature }}</li>
                    @endforeach
                    <li><span class="bl-check" aria-hidden="true">✓</span>Work Log &amp; Tasks</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ── Upgrade / renew ───────────────────────────────────────────── --}}
    @if($quotes)
    <section class="bl-card" aria-labelledby="upgrade-title">
        <div class="bl-checkout">
            <div>
                <h2 class="bl-card-title" id="upgrade-title">{{ $isPro ? 'Renew or extend Pro' : 'Upgrade to Pro' }}</h2>
                <p class="bl-card-sub" style="margin-bottom:1rem">
                    @if($isPro)
                        Adds another period after {{ $expires?->format('j M Y') ?? 'today' }}. Nothing changes until then.
                    @else
                        Pro features switch on as soon as the payment goes through — no need to log in again.
                    @endif
                </p>

                <div class="bl-features-title">{{ $isPro ? 'Pro includes' : 'You get everything in Free, plus' }}</div>
                <ul class="bl-features">
                    @foreach($proFeatures as $feature)
                        <li><span class="bl-check" aria-hidden="true">✓</span>{{ $feature }}</li>
                    @endforeach
                    <li><span class="bl-check" aria-hidden="true">✓</span>No limit on people</li>
                </ul>
            </div>

            <div>
                @include('billing.partials.checkout', [
                    'quotes' => $quotes,
                    'period' => $period,
                    'verb'   => $isPro ? 'extend' : 'upgrade',
                    'uid'    => 'bl',
                ])
            </div>
        </div>
    </section>
    @endif

    {{-- ── Manage plan (refund / switch to Free) ────────────────────── --}}
    @if($isPro && ($refundable || !$scheduled))
    <section class="bl-card" aria-labelledby="manage-title">
        <h2 class="bl-card-title" id="manage-title">Change or cancel</h2>

        @if($refundable)
        <div class="bl-manage-row">
            <div class="bl-manage-text">
                <strong>Cancel and get a full refund</strong>
                You paid {{ $inr($refundable->amount) }} on {{ $refundable->paid_at->format('j M Y') }}.
                Payments can be refunded in full within {{ $refundDays }} days — until
                {{ $refundable->paid_at->copy()->addDays($refundDays)->format('j M Y, g:i A') }}.
            </div>
            <button type="button" class="bl-btn bl-btn-secondary bl-btn-sm" data-dialog-open="refund-dialog">Cancel &amp; refund</button>
        </div>
        @endif

        @if(!$scheduled)
        <div class="bl-manage-row">
            <div class="bl-manage-text">
                <strong>Switch to Free</strong>
                Keep Pro until {{ $expires ? $expires->format('j M Y') : 'today' }}, then move to the Free plan.
                @if($refundable) This doesn’t refund your payment — use “Cancel &amp; refund” for that. @endif
            </div>
            <button type="button" class="bl-btn bl-btn-secondary bl-btn-sm" data-dialog-open="downgrade-dialog">Switch to Free</button>
        </div>
        @endif
    </section>
    @endif

    {{-- ── Transaction history ──────────────────────────────────────── --}}
    <section class="bl-card" aria-labelledby="history-title">
        <h2 class="bl-card-title" id="history-title" style="margin-bottom:1rem">Transaction history</h2>

        @if($payments->isEmpty())
            <div class="bl-empty">No payments yet. Payments and refunds will appear here with downloadable receipts.</div>
        @else
            <table class="bl-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Paid by</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th><span class="sr-only">Receipt</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($payments as $p)
                    @php
                        $pill = match (true) {
                            $p->isPaid()                       => 'bl-pill-green',
                            $p->isRefunded() && $p->refund_status === 'failed' => 'bl-pill-red',
                            $p->isRefunded()                   => 'bl-pill-blue',
                            $p->status === 'failed'            => 'bl-pill-red',
                            default                            => 'bl-pill-grey',
                        };
                        $when = $p->paid_at ?? $p->created_at;
                    @endphp
                    <tr>
                        <td data-label="Date">
                            {{ $when->format('j M Y') }}
                            <div class="bl-muted">{{ $when->format('g:i A') }}</div>
                        </td>
                        <td data-label="Description">
                            {{ $p->description() }}
                            @if($p->period_start && $p->period_end)
                                <div class="bl-muted">{{ $p->period_start->format('j M Y') }} → {{ $p->period_end->format('j M Y') }}</div>
                            @endif
                            @if($p->receipt_number)
                                <div class="bl-muted">{{ $p->receipt_number }}</div>
                            @endif
                        </td>
                        <td data-label="Paid by">
                            {{ $p->paidBy?->name ?? '—' }}
                            @if($p->paidBy)<div class="bl-muted">{{ $p->paidBy->email }}</div>@endif
                        </td>
                        <td data-label="Amount" class="bl-num">{{ $inr($p->amount) }}</td>
                        <td data-label="Status">
                            <span class="bl-pill {{ $pill }}">{{ $p->statusLabel() }}</span>
                            @if($p->status === 'failed' && $p->failure_reason)
                                <div class="bl-muted">{{ $p->failure_reason }}</div>
                            @endif
                            @if($p->isRefunded() && $p->refunded_at)
                                <div class="bl-muted">{{ $inr($p->refund_amount ?? $p->amount) }} on {{ $p->refunded_at->format('j M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            @if(in_array($p->status, ['paid', 'refunded'], true))
                                <a class="bl-link" href="{{ route('billing.receipt', $p->id) }}" target="_blank" rel="noopener">Receipt</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($payments->hasPages())
                <div class="bl-pagination">{{ $payments->links() }}</div>
            @endif
        @endif
    </section>

    @if($plan !== 'enterprise')
    <p class="bl-sub" style="text-align:center">
        Need SSO, API access or more than {{ config('plans.enterprise.min_seats') }} people?
        <a class="bl-link" href="{{ route('contact', ['plan' => 'enterprise']) }}">Talk to us about Enterprise</a>
        · <a class="bl-link" href="{{ route('refund-policy') }}" target="_blank" rel="noopener">Refund policy</a>
    </p>
    @endif
</div>

{{-- ── Confirm dialogs ──────────────────────────────────────────────── --}}
@if($refundable)
<dialog class="bl-dialog" id="refund-dialog" aria-labelledby="refund-dialog-title">
    <form method="POST" action="{{ route('billing.refund', $refundable->id) }}" data-confirm-form>
        @csrf
        <h2 id="refund-dialog-title">Cancel Pro and get a refund?</h2>
        <ul>
            <li>{{ $inr($refundable->amount) }} goes back to the original payment method, usually within 5–7 working days.</li>
            @if($refundKeepsProUntil)
                <li>Pro stays active until {{ $refundKeepsProUntil->format('j M Y') }} from your earlier payment.</li>
            @else
                <li>Your organization moves to Free straight away, and Pro features switch off.</li>
            @endif
            <li>Nothing is deleted — you can upgrade again at any time.</li>
            @if(!$refundKeepsProUntil && $activeUsers > $freeLimit)
                <li>You have {{ $activeUsers }} people; Free allows {{ $freeLimit }}. Everyone keeps access, but you can’t add people until you upgrade.</li>
            @endif
        </ul>
        <label for="refund-reason">Why are you cancelling? (optional)</label>
        <textarea id="refund-reason" name="reason" maxlength="1000"></textarea>
        <div class="bl-dialog-actions">
            <button type="button" class="bl-btn bl-btn-secondary" data-dialog-close>Keep Pro</button>
            <button type="submit" class="bl-btn bl-btn-danger">Cancel &amp; refund {{ $inr($refundable->amount) }}</button>
        </div>
    </form>
</dialog>
@endif

@if($isPro && !$scheduled)
<dialog class="bl-dialog" id="downgrade-dialog" aria-labelledby="downgrade-dialog-title">
    <form method="POST" action="{{ route('billing.downgrade') }}" data-confirm-form>
        @csrf
        <h2 id="downgrade-dialog-title">Switch to Free{{ $expires ? ' on ' . $expires->format('j M Y') : '' }}?</h2>
        <ul>
            @if($expires)
                <li>Pro features stay on until {{ $expires->format('j M Y') }}. You won’t be charged again.</li>
            @endif
            <li>After that, {{ $proFeatures->take(4)->implode(', ') }} and other Pro features switch off.</li>
            <li>Your data is kept, and you can upgrade again at any time.</li>
            @if($activeUsers > $freeLimit)
                <li>You have {{ $activeUsers }} people; Free allows {{ $freeLimit }}. Everyone keeps access, but you can’t add people until you upgrade.</li>
            @endif
        </ul>
        <div class="bl-dialog-actions">
            <button type="button" class="bl-btn bl-btn-secondary" data-dialog-close>Keep Pro</button>
            <button type="submit" class="bl-btn bl-btn-primary">Switch to Free</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ asset('js/billing.js') }}"></script>
@endpush
