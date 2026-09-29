@extends('layouts.app')
@section('title', $moduleLabel . ' — Upgrade')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}">
@endpush

@section('content')
<div class="bl-gate">
    <div class="bl-gate-card">
        <span class="bl-pill bl-pill-blue bl-gate-badge">{{ ucfirst($planNeeded) }} feature</span>

        <h1>{{ $moduleLabel }} is included in {{ ucfirst($planNeeded) }}</h1>
        <p>
            @if($moduleDesc){{ $moduleDesc }}. @endif
            Your organization is on the {{ ucfirst($currentPlan) }} plan.
        </p>

        @if($canBuy && $quotes)
            <p class="bl-gate-note">Upgrade here and this page opens as soon as the payment goes through.</p>
            <div class="bl-gate-checkout">
                @include('billing.partials.checkout', [
                    'quotes' => $quotes,
                    'period' => 'yearly',
                    'verb'   => 'upgrade',
                    'uid'    => 'gate',
                    'afterPayUrl' => request()->isMethod('GET') ? request()->fullUrl() : route('dashboard'),
                ])
            </div>
        @elseif($canBuy)
            <p class="bl-gate-note">{{ ucfirst($planNeeded) }} is set up with our team.</p>
            <a href="{{ route('contact', ['plan' => $planNeeded]) }}" class="bl-btn bl-btn-primary bl-pay">Talk to us</a>
        @else
            <div class="bl-summary">
                Only your organization’s owner or an admin can change the plan.
                @if($owner)
                    Ask <strong>{{ $owner->name }}</strong>
                    (<a class="bl-link" href="mailto:{{ $owner->email }}?subject={{ rawurlencode('Upgrade OutraqHQ to ' . ucfirst($planNeeded)) }}">{{ $owner->email }}</a>)
                    to upgrade.
                @endif
            </div>
        @endif

        <div class="bl-gate-links">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">← Go back</a>
            @if($canBuy)
                <a href="{{ route('billing.index') }}">See billing &amp; plans</a>
            @endif
        </div>
    </div>
</div>
@endsection

@if($canBuy && $quotes)
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ asset('js/billing.js') }}"></script>
@endpush
@endif
