@extends('layouts.app')

@section('content')
<div class="page-container flex-center min-h-60">
    <div class="fv-card p-xl text-center max-w-md">
        <div class="dash-live-pill-green inline-block mb-md bg-red-100 text-red-700">
            <span class="dash-live-text-green font-bold text-red-700">TOKEN EXPIRED</span>
        </div>
        <h1 class="heading-xl mb-sm">Link Has Expired ⏳</h1>
        <p class="text-muted-sm mb-lg">This magic action link has expired or has already been used. Please log in to complete the action.</p>
        <a href="{{ route('dashboard') }}" class="fv-btn fv-btn-primary">Go to Dashboard</a>
    </div>
</div>
@endsection
