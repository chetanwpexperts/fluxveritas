@extends('layouts.app')

@section('content')
<div class="page-container flex-center min-h-60">
    <div class="fv-card p-xl text-center max-w-md">
        <div class="dash-live-pill-green inline-block mb-md">
            <span class="dash-live-text-green font-bold">1-CLICK ACTION EXECUTED</span>
        </div>
        <h1 class="heading-xl mb-sm">{{ $title ?? 'Action Successful!' }}</h1>
        <p class="text-muted-sm mb-lg">{{ $message ?? 'Your executive action was completed safely.' }}</p>
        <a href="{{ route('dashboard') }}" class="fv-btn fv-btn-primary">Return to Dashboard</a>
    </div>
</div>
@endsection
