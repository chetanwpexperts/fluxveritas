{{-- Organization status pill. Needs: $org --}}
@php
    $status = $org->status ?: 'active';
    $pill   = ['active' => 'po-pill-active', 'pending' => 'po-pill-pending', 'suspended' => 'po-pill-suspended'][$status] ?? 'po-pill-neutral';
@endphp
<span class="po-pill {{ $pill }}">{{ ucfirst($status) }}</span>
