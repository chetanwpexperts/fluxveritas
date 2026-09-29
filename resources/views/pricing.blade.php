@extends('layouts.public')
@section('title', 'Pricing')

@section('meta')
    <meta name="description" content="OutraqHQ pricing in INR. Free for up to {{ config('plans.free.max_users') }} people. Pro from {{ \App\Services\BillingService::inr(config('plans.pro.periods.yearly.price_per_user')) }} per user per month. Cancel within {{ config('plans.refund_window_days') }} days for a full refund.">
    <link rel="canonical" href="{{ route('pricing') }}">
@endsection

@php
    $inr        = fn (int $p) => \App\Services\BillingService::inr($p);
    $monthly    = config('plans.pro.periods.monthly.price_per_user');
    $yearly     = config('plans.pro.periods.yearly.price_per_user');
    $saving     = \App\Services\BillingService::yearlySavingPercent();
    $freeUsers  = config('plans.free.max_users');
    $minSeats   = config('plans.pro.min_seats');
    $entFrom    = config('plans.enterprise.from_price_per_user');
    $entSeats   = config('plans.enterprise.min_seats');
    $gst        = config('plans.gst_percent');
    $refundDays = config('plans.refund_window_days');

    $user       = auth()->user();
    $canBuy     = $user && $user->organization_id && $user->hasAnyRole(['owner', 'admin']);
    $orgPlan    = $user?->organization?->effectivePlan();

    // [feature, free, pro, enterprise] — true/false or a short text value
    $groups = [
        'People' => [
            ['People included', "Up to {$freeUsers}", 'Unlimited', 'Unlimited'],
        ],
        'Core HR' => [
            ['Employee directory & org chart', true, true, true],
            ['Leave requests, balances & approvals', true, true, true],
            ['Document center', true, true, true],
            ['Onboarding checklists', true, true, true],
            ['Announcements', true, true, true],
            ['Import employees from CSV, Excel or JSON', true, true, true],
        ],
        'Work tracking' => [
            ['Daily work logs', true, true, true],
            ['Tasks & sprints', true, true, true],
            ['GitHub activity', true, true, true],
            ['Blockers & dependencies', false, true, true],
        ],
        'Performance' => [
            ['Manager feedback & employee statements', true, true, true],
            ['Increment calculator & annual reviews', false, true, true],
            ['Fairness checks on workload', false, true, true],
            ['Peer feedback & bias reports', false, true, true],
        ],
        'Reports & AI' => [
            ['Team & individual reports', false, true, true],
            ['HR reports (headcount, leave, profiles)', false, true, true],
            ['AI summaries & questions about your data', false, true, true],
        ],
        'For larger organizations' => [
            ['Command Center for leadership', false, false, true],
            ['Audit logs', false, false, true],
            ['Help moving your existing data in', false, false, true],
            ['Priority support', false, false, true],
        ],
    ];
@endphp

@section('content')
<main class="lp">

    <section class="lp-hero" style="padding-bottom:40px">
        <div class="lp-wrap" style="text-align:center">
            <h1 class="lp-h1" style="font-size:clamp(2rem,4.5vw,3rem)">Simple pricing in rupees</h1>
            <p class="lp-lead">Start free. Pay per person when you need increments, fairness checks and reports.</p>

            <div class="pr-toggle" role="group" aria-label="Billing period">
                <button type="button" data-pr-period="monthly" aria-pressed="false">Monthly</button>
                <button type="button" data-pr-period="yearly" aria-pressed="true">Yearly @if($saving > 0)<span class="pr-save">Save {{ $saving }}%</span>@endif</button>
            </div>
        </div>
    </section>

    <section style="padding-bottom:72px">
        <div class="lp-wrap">
            <div class="lp-plans pr-plans">

                {{-- FREE --}}
                <div class="lp-plan">
                    <h3>Free @if($orgPlan === 'free')<span class="pr-badge">Your plan</span>@endif</h3>
                    <div class="lp-plan-price">₹0</div>
                    <div class="pr-plan-meta">Free forever for up to {{ $freeUsers }} people. No card needed.</div>
                    <ul>
                        <li>Directory, leave, documents &amp; onboarding</li>
                        <li>Work logs, tasks &amp; sprints</li>
                        <li>Announcements</li>
                        <li>GitHub activity</li>
                        <li>Employee import</li>
                    </ul>
                    @guest
                        <a href="{{ route('register') }}" class="lp-btn lp-btn-secondary">Start free</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="lp-btn lp-btn-secondary">Go to dashboard</a>
                    @endguest
                </div>

                {{-- PRO --}}
                <div class="lp-plan lp-plan-featured">
                    <h3>Pro @if($orgPlan === 'pro')<span class="pr-badge">Your plan</span>@endif</h3>
                    <div class="lp-plan-price">
                        <span data-pr-show="yearly">{{ $inr($yearly) }}</span><span data-pr-show="monthly" hidden>{{ $inr($monthly) }}</span><small>/user/month</small>
                    </div>
                    <div class="pr-plan-meta">
                        <span data-pr-show="yearly">Billed yearly. Minimum {{ $minSeats }} users.</span>
                        <span data-pr-show="monthly" hidden>Billed monthly. Minimum {{ $minSeats }} users.</span>
                    </div>
                    <ul>
                        <li>Everything in Free, for unlimited people</li>
                        <li>Increment calculator &amp; annual reviews</li>
                        <li>Fairness checks on workload</li>
                        <li>Peer feedback &amp; bias reports</li>
                        <li>Team, individual &amp; HR reports</li>
                        <li>Blockers &amp; dependencies</li>
                        <li>AI summaries</li>
                    </ul>
                    @if($canBuy && $orgPlan !== 'enterprise')
                        <a href="{{ route('billing.index', ['period' => 'yearly']) }}" class="lp-btn lp-btn-primary" data-pr-link>
                            {{ $orgPlan === 'pro' ? 'Renew Pro' : 'Upgrade to Pro' }}
                        </a>
                    @elseif($user && $orgPlan !== 'enterprise')
                        <p class="pr-plan-meta" style="min-height:0;margin:0;text-align:center">Ask your organization’s owner or an admin to upgrade.</p>
                    @elseif(!$user)
                        <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">Start free, upgrade anytime</a>
                    @endif
                </div>

                {{-- ENTERPRISE --}}
                <div class="lp-plan">
                    <h3>Enterprise @if($orgPlan === 'enterprise')<span class="pr-badge">Your plan</span>@endif</h3>
                    <div class="lp-plan-price">Custom</div>
                    <div class="pr-plan-meta">From {{ $inr($entFrom) }}/user/month on a yearly contract, for {{ $entSeats }}+ people.</div>
                    <ul>
                        <li>Everything in Pro</li>
                        <li>Command Center for leadership</li>
                        <li>Audit logs</li>
                        <li>Help moving your existing data in</li>
                        <li>Priority support</li>
                    </ul>
                    <a href="{{ route('contact', ['plan' => 'enterprise']) }}" class="lp-btn lp-btn-secondary">Talk to us</a>
                </div>
            </div>

            <p class="lp-plans-note">
                All prices in INR{{ $gst > 0 ? ' and exclude ' . rtrim(rtrim(number_format($gst, 2), '0'), '.') . '% GST' : '' }}.
                Plans are paid in advance and don’t renew automatically.
                Cancel within {{ $refundDays }} days for a full refund.
            </p>
        </div>
    </section>

    <section class="lp-section lp-alt">
        <div class="lp-wrap">
            <div class="lp-head">
                <h2 class="lp-h2">Compare plans</h2>
            </div>
            <div class="pr-table-wrap">
                <table class="pr-table">
                    <thead>
                        <tr><th scope="col">Feature</th><th scope="col">Free</th><th scope="col">Pro</th><th scope="col">Enterprise</th></tr>
                    </thead>
                    <tbody>
                    @foreach($groups as $group => $rows)
                        <tr class="pr-group"><td colspan="4">{{ $group }}</td></tr>
                        @foreach($rows as [$label, $free, $pro, $ent])
                            <tr>
                                <td>{{ $label }}</td>
                                @foreach([$free, $pro, $ent] as $cell)
                                    <td>
                                        @if($cell === true)
                                            <span class="pr-yes" aria-label="Included">✓</span>
                                        @elseif($cell === false)
                                            <span class="pr-no" aria-label="Not included">—</span>
                                        @else
                                            {{ $cell }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="lp-section">
        <div class="lp-wrap lp-faq-wrap">
            <div class="lp-head">
                <h2 class="lp-h2">Billing questions</h2>
            </div>
            <div class="lp-faq">
                <details>
                    <summary>Who counts as a user?</summary>
                    <p>Every active person in your organization — employees, managers, HR and admins. Deactivated people don’t count. Pro is billed for at least {{ $minSeats }} users.</p>
                </details>
                <details>
                    <summary>What if we add people after paying?</summary>
                    <p>New people can join straight away. Your price is based on the number of active people when you pay, so your next payment covers the new total.</p>
                </details>
                <details>
                    <summary>Can I switch between monthly and yearly?</summary>
                    <p>Yes. When you renew, pick either option. The new period starts when your current one ends, so you never lose paid time.</p>
                </details>
                <details>
                    <summary>How do refunds work?</summary>
                    <p>Cancel from the Billing page within {{ $refundDays }} days of a payment and the full amount goes back to your original payment method, usually within 5–7 working days. After {{ $refundDays }} days, you can still switch to Free — Pro stays active until the end of the period you paid for. <a href="{{ route('refund-policy') }}">Full refund policy</a>.</p>
                </details>
                <details>
                    <summary>How can we pay?</summary>
                    <p>UPI, debit and credit cards, and net banking, processed securely by Razorpay. A receipt for every payment is available on the Billing page.</p>
                </details>
                <details>
                    <summary>What happens when Pro ends?</summary>
                    <p>Your organization moves to the Free plan. Nothing is deleted — Pro features switch back on as soon as you renew.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="lp-final">
        <div class="lp-wrap">
            <h2 class="lp-h2">Try OutraqHQ free</h2>
            <p class="lp-sub">Set up your organization in minutes. Upgrade when you’re ready.</p>
            <div class="lp-ctas">
                <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">Start free</a>
                <a href="{{ route('contact') }}" class="lp-btn lp-btn-secondary">Book a demo</a>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
(function () {
    var buttons = document.querySelectorAll('[data-pr-period]');
    var link = document.querySelector('[data-pr-link]');

    function show(period) {
        buttons.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.prPeriod === period ? 'true' : 'false'); });
        document.querySelectorAll('[data-pr-show]').forEach(function (el) { el.hidden = el.dataset.prShow !== period; });
        if (link) {
            var url = new URL(link.href);
            url.searchParams.set('period', period);
            link.href = url.toString();
        }
    }

    buttons.forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.prPeriod); }); });
})();
</script>
@endpush
