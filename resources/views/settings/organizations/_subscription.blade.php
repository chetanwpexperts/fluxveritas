{{-- One-line subscription state. Needs: $org --}}
@php
    $plan    = $org->effectivePlan();
    $expires = $org->plan_expires_at;
@endphp
@if($plan === 'free')
    {{ $org->billing_status === 'expired' ? 'Expired — now on Free' : 'Free' }}
@elseif($org->downgrade_scheduled_at && $expires)
    Ends {{ $expires->format('j M Y') }} (switching to Free)
@elseif($expires)
    Active until {{ $expires->format('j M Y') }}
@else
    Active, no end date
@endif
