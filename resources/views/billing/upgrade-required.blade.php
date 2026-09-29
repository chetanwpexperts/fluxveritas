@extends('layouts.app')
@section('title', 'Upgrade Required')
@section('content')

<div style="max-width:520px;margin:0 auto;padding:4rem 1rem;text-align:center">

    <div style="width:56px;height:56px;border-radius:14px;background:#18181b;
                display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
        <span style="color:#fff;font-size:24px">★</span>
    </div>

    <h1 style="font-size:24px;font-weight:600;color:#18181b;margin-bottom:8px">
        {{ $moduleLabel }} is a {{ ucfirst($planNeeded) }} feature
    </h1>

    <p style="font-size:14px;color:#6b7280;margin-bottom:2rem;line-height:1.6">
        @if($moduleDesc){{ $moduleDesc }}.<br>@endif
        Your organization is on the <strong style="text-transform:capitalize">{{ $currentPlan }}</strong> plan.
        Upgrade to {{ ucfirst($planNeeded) }} to unlock this feature.
    </p>

    @if($canBuy)
        @if($planNeeded === 'enterprise')
            <a href="{{ route('contact', ['plan' => 'enterprise']) }}"
               style="display:inline-block;background:#18181b;color:#fff;padding:12px 28px;
                      border-radius:8px;font-size:14px;font-weight:500;text-decoration:none">
                Contact Sales →
            </a>
        @else
            <a href="{{ route('billing.index') }}"
               style="display:inline-block;background:#18181b;color:#fff;padding:12px 28px;
                      border-radius:8px;font-size:14px;font-weight:500;text-decoration:none">
                Upgrade to Pro — ₹199/mo →
            </a>
        @endif
    @else
        <div style="background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:10px;
                    padding:1rem;font-size:13px;color:#6b7280">
            Ask your organization's owner or admin to upgrade the plan.
        </div>
    @endif

    <div style="margin-top:1.5rem">
        <a href="{{ route('dashboard') }}"
           style="font-size:13px;color:#6b7280;text-decoration:none">
            ← Back to dashboard
        </a>
    </div>

</div>
@endsection
