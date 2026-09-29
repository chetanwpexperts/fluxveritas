@extends('layouts.public')
@section('title', 'Pricing')
@section('content')

<div style="padding:3rem 2rem;max-width:900px;margin:0 auto;text-align:center">

  <p style="font-size:13px;font-weight:500;color:#3b6d11;background:#eaf3de;
             display:inline-block;padding:4px 14px;border-radius:999px;margin-bottom:1rem">
    🎉 Free Beta — All features free until v1.0
  </p>

  <h1 style="font-size:32px;font-weight:500;color:#18181b;margin-bottom:12px">
    Simple, Transparent Pricing
  </h1>
  <p style="font-size:15px;color:#6b7280;margin-bottom:3rem">
    Start free. Add the merit engine when you're ready. Contact us for enterprise.
  </p>

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;margin-bottom:3rem;text-align:left">

    {{-- FREE --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:16px;padding:1.5rem">
      <div style="font-size:13px;font-weight:500;color:#6b7280;margin-bottom:8px">Free</div>
      <div style="font-size:36px;font-weight:500;color:#18181b;margin-bottom:4px">₹0</div>
      <div style="font-size:12px;color:#9ca3af;margin-bottom:1.5rem">The daily basics, free forever</div>
      <div style="height:0.5px;background:#f3f4f6;margin-bottom:1.5rem"></div>
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:1.5rem">
        @foreach([
          'GitHub Sync',
          'Employee Directory',
          'Leave Management',
          'Document Center',
          'Onboarding Checklists',
          'Announcements',
        ] as $feature)
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#18181b">
          <span style="color:#3b6d11;font-size:14px">✓</span> {{ $feature }}
        </div>
        @endforeach
      </div>
      <a href="{{ route('register') }}"
         style="display:block;text-align:center;background:#f3f4f6;color:#18181b;
                padding:10px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none">
        Get Started Free
      </a>
    </div>

    {{-- PRO (FEATURED) --}}
    <div style="background:#18181b;border:2px solid #18181b;border-radius:16px;
                padding:1.5rem;position:relative">
      <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);
                  background:#10b981;color:#fff;font-size:11px;font-weight:600;
                  padding:3px 12px;border-radius:999px;white-space:nowrap">
        Most Popular
      </div>
      <div style="font-size:13px;font-weight:500;color:#9ca3af;margin-bottom:8px">Pro</div>
      <div style="font-size:36px;font-weight:500;color:#fff;margin-bottom:4px">
        ₹199 <span style="font-size:14px;color:#6b7280;font-weight:400">/user/mo</span>
      </div>
      <div style="font-size:12px;color:#6b7280;margin-bottom:1.5rem">The merit &amp; fairness engine</div>
      <div style="height:0.5px;background:#333;margin-bottom:1.5rem"></div>
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:1.5rem">
        @foreach([
          'Everything in Free',
          'Fairness Engine',
          'AI Intelligence',
          'Increment Calculator',
          'Reports &amp; Analytics',
          'HR Reports',
          'Blockers &amp; Dependencies',
        ] as $feature)
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#d1d5db">
          <span style="color:#10b981;font-size:14px">✓</span> {!! $feature !!}
        </div>
        @endforeach
      </div>
      <a href="{{ route('billing.index') }}"
         style="display:block;text-align:center;background:#fff;color:#18181b;
                padding:10px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none">
        Upgrade to Pro →
      </a>
    </div>

    {{-- ENTERPRISE --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:16px;padding:1.5rem">
      <div style="font-size:13px;font-weight:500;color:#6b7280;margin-bottom:8px">Enterprise</div>
      <div style="font-size:36px;font-weight:500;color:#18181b;margin-bottom:4px">Custom</div>
      <div style="font-size:12px;color:#9ca3af;margin-bottom:1.5rem">Leadership visibility &amp; control</div>
      <div style="height:0.5px;background:#f3f4f6;margin-bottom:1.5rem"></div>
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:1.5rem">
        @foreach([
          'Everything in Pro',
          'Command Center (CEO view)',
          'API Access',
          'SSO / SAML',
          'Audit Logs',
          'Priority Support',
        ] as $feature)
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#18181b">
          <span style="color:#3b6d11;font-size:14px">✓</span> {{ $feature }}
        </div>
        @endforeach
      </div>
      <a href="{{ route('contact', ['plan' => 'enterprise']) }}"
         style="display:block;text-align:center;background:#f3f4f6;color:#18181b;
                padding:10px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none">
        Contact Sales →
      </a>
    </div>

  </div>

  {{-- BETA BANNER --}}
  <div style="background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:12px;
              padding:1.5rem;text-align:center">
    <div style="font-size:15px;font-weight:500;color:#18181b;margin-bottom:6px">
      🚀 Currently in Free Beta
    </div>
    <div style="font-size:13px;color:#6b7280;max-width:500px;margin:0 auto">
      All features are free during our beta period. Early adopters will receive
      special pricing when we launch paid plans. No credit card required.
    </div>
  </div>

</div>
@endsection
