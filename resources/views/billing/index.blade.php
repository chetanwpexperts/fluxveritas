@extends('layouts.app')
@section('title', 'Billing')

@section('content')
<div style="max-width:760px;margin:0 auto;padding:2rem 1rem">

    <h1 style="font-size:22px;font-weight:500;color:#18181b;margin-bottom:4px">Billing</h1>
    <p style="font-size:13px;color:#6b7280;margin-bottom:1.5rem">Manage your organization's plan</p>

    @if(session('success'))
    <div style="background:#eaf3de;border:0.5px solid #c3e6a0;border-radius:8px;padding:12px 16px;margin-bottom:1rem;font-size:13px;color:#3b6d11">
        {{ session('success') }}
    </div>
    @endif

    {{-- Current plan card --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
            <div>
                <div style="font-size:12px;color:#6b7280;margin-bottom:2px">Current Plan</div>
                <div style="font-size:20px;font-weight:600;color:#18181b;text-transform:capitalize">
                    {{ $org->plan ?? 'Free' }}
                </div>
                <div style="font-size:12px;color:#6b7280;margin-top:4px">
                    Status:
                    <span style="font-weight:500;
                        @if($org->billing_status === 'active') color:#3b6d11
                        @elseif($org->billing_status === 'expired') color:#a32d2d
                        @else color:#6b7280 @endif">
                        {{ ucfirst($org->billing_status ?? 'free') }}
                    </span>
                </div>
                @if($org->plan_expires_at)
                <div style="font-size:12px;color:#6b7280;margin-top:2px">
                    @if(\Carbon\Carbon::parse($org->plan_expires_at)->isFuture())
                        Renews on {{ \Carbon\Carbon::parse($org->plan_expires_at)->format('M j, Y') }}
                    @else
                        Expired on {{ \Carbon\Carbon::parse($org->plan_expires_at)->format('M j, Y') }}
                    @endif
                </div>
                @endif
            </div>

            @if(($org->plan ?? 'free') === 'free' || ($org->plan_expires_at && \Carbon\Carbon::parse($org->plan_expires_at)->isPast()))
            <button id="upgrade-btn"
                    style="background:#18181b;color:#fff;border:none;padding:10px 22px;
                           border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;
                           white-space:nowrap">
                Upgrade to Pro — ₹199/mo
            </button>
            @else
            <button id="upgrade-btn"
                    style="background:#f3f4f6;color:#18181b;border:0.5px solid #e5e7eb;
                           padding:10px 22px;border-radius:8px;font-size:14px;
                           font-weight:500;cursor:pointer;white-space:nowrap">
                Extend 1 month — ₹199
            </button>
            @endif
        </div>
    </div>

    {{-- Plan comparison --}}
    <div style="margin-bottom:1.5rem">
        <div style="font-size:14px;font-weight:500;color:#18181b;margin-bottom:2px">
            Plans
        </div>
        <p style="font-size:12px;color:#6b7280;margin-bottom:1rem">
            Choose the plan that fits your team.
        </p>

        @php $current = $org->plan ?? 'free'; @endphp

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px">

            {{-- FREE --}}
            <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:1.25rem;position:relative">
                @if($current === 'free')
                <div style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:600;
                            background:#f3f4f6;color:#6b7280;padding:2px 10px;border-radius:999px">Current</div>
                @endif
                <div style="font-size:13px;color:#6b7280">Free</div>
                <div style="font-size:20px;font-weight:600;color:#18181b;margin:2px 0 4px">₹0</div>
                <div style="font-size:11px;color:#9ca3af;margin-bottom:12px">The daily basics</div>
                <div style="font-size:12px;color:#374151;line-height:1.9;border-top:0.5px solid #f3f4f6;padding-top:10px">
                    <div>✓ Members &amp; Teams</div>
                    <div>✓ Work Log &amp; Tasks</div>
                    <div>✓ Leave Management</div>
                    <div>✓ Directory &amp; Documents</div>
                    <div>✓ Announcements &amp; Onboarding</div>
                    <div>✓ GitHub Sync</div>
                </div>
            </div>

            {{-- PRO --}}
            <div style="background:#fff;border:2px solid #18181b;border-radius:12px;padding:1.25rem;position:relative">
                @if($current === 'pro')
                <div style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:600;
                            background:#eaf3de;color:#3b6d11;padding:2px 10px;border-radius:999px">Current</div>
                @else
                <div style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:600;
                            background:#18181b;color:#fff;padding:2px 10px;border-radius:999px">Popular</div>
                @endif
                <div style="font-size:13px;color:#6b7280">Pro</div>
                <div style="font-size:20px;font-weight:600;color:#18181b;margin:2px 0 4px">
                    ₹199<span style="font-size:12px;color:#6b7280;font-weight:400">/user/mo</span>
                </div>
                <div style="font-size:11px;color:#9ca3af;margin-bottom:12px">The merit &amp; fairness engine</div>
                <div style="font-size:12px;color:#374151;line-height:1.9;border-top:0.5px solid #f3f4f6;padding-top:10px">
                    <div>★ Everything in Free</div>
                    <div>★ Increment Calculator</div>
                    <div>★ Fairness Engine</div>
                    <div>★ AI Intelligence</div>
                    <div>★ Reports &amp; Analytics</div>
                    <div>★ Blockers &amp; Dependencies</div>
                </div>
            </div>

            {{-- ENTERPRISE --}}
            <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:1.25rem;position:relative">
                @if($current === 'enterprise')
                <div style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:600;
                            background:#eaf3de;color:#3b6d11;padding:2px 10px;border-radius:999px">Current</div>
                @endif
                <div style="font-size:13px;color:#6b7280">Enterprise</div>
                <div style="font-size:20px;font-weight:600;color:#18181b;margin:2px 0 4px">Custom</div>
                <div style="font-size:11px;color:#9ca3af;margin-bottom:12px">Leadership &amp; control</div>
                <div style="font-size:12px;color:#374151;line-height:1.9;border-top:0.5px solid #f3f4f6;padding-top:10px">
                    <div>✓ Everything in Pro</div>
                    <div>✓ Command Center (CEO view)</div>
                    <div>✓ API Access</div>
                    <div>✓ SSO / SAML</div>
                    <div>✓ Audit Logs</div>
                    <div>✓ Priority Support</div>
                </div>
                <a href="{{ route('contact', ['plan' => 'enterprise']) }}"
                   style="display:block;text-align:center;margin-top:12px;background:#f3f4f6;
                          color:#18181b;border:0.5px solid #e5e7eb;padding:8px;border-radius:8px;
                          font-size:13px;font-weight:500;text-decoration:none">
                    Contact Sales →
                </a>
            </div>

        </div>
    </div>

    {{-- Payment history --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:1.5rem">
        <div style="font-size:14px;font-weight:500;color:#18181b;margin-bottom:1rem">Payment History</div>

        @forelse($payments as $p)
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:10px 0;border-bottom:0.5px solid #f3f4f6;font-size:13px">
            <div>
                <span style="font-weight:500;text-transform:capitalize;color:#18181b">
                    {{ ucfirst($p->plan) }} — {{ ucfirst($p->billing_period) }}
                </span>
                <div style="color:#9ca3af;font-size:12px;margin-top:2px">
                    {{ $p->created_at->format('M j, Y · g:i A') }}
                    @if($p->razorpay_payment_id)
                    · <span style="font-family:monospace">{{ $p->razorpay_payment_id }}</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
                <span style="font-weight:500;color:#18181b">₹{{ number_format($p->amount / 100, 0) }}</span>
                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;
                    @if($p->status === 'paid') background:#eaf3de;color:#3b6d11
                    @elseif($p->status === 'failed') background:#fcebeb;color:#a32d2d
                    @else background:#f3f4f6;color:#6b7280 @endif">
                    {{ ucfirst($p->status) }}
                </span>
            </div>
        </div>
        @empty
        <div style="font-size:13px;color:#9ca3af;text-align:center;padding:1.5rem 0">
            No payments yet
        </div>
        @endforelse
    </div>

</div>

{{-- Razorpay checkout --}}
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('upgrade-btn')?.addEventListener('click', async function () {
    const btn = this;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Loading…';

    try {
        const res = await fetch('{{ route("billing.order") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ plan: 'pro' }),
        });

        if (!res.ok) {
            throw new Error('Failed to create order');
        }

        const order = await res.json();

        const rzp = new Razorpay({
            key:         order.key,
            amount:      order.amount,
            currency:    order.currency,
            name:        order.name,
            description: order.description,
            order_id:    order.order_id,
            prefill:     order.prefill,
            theme:       { color: '#18181b' },
            handler: async function (response) {
                try {
                    const verifyRes = await fetch('{{ route("billing.verify") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            razorpay_order_id:   response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature:  response.razorpay_signature,
                        }),
                    });
                    const result = await verifyRes.json();
                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        alert(result.message || 'Verification failed. Please contact support.');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                } catch (e) {
                    alert('Verification error. Please contact support.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            },
            modal: {
                ondismiss: function () {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }
        });
        rzp.open();

    } catch (err) {
        alert('Something went wrong. Please try again.');
        btn.disabled = false;
        btn.textContent = originalText;
    }
});
</script>
@endsection
