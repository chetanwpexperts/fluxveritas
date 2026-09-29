@extends('layouts.app')

@section('title', 'My Onboarding')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hr-onboarding.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <h1 class="onb-title">My Onboarding Checklist</h1>

    @if(session('success'))
        <div class="onb-alert">{{ session('success') }}</div>
    @endif

    @if(!$checklist)
        <div class="onb-empty">Your onboarding checklist has not been set up yet. Contact HR for assistance.</div>
    @else
        @include('hr-onboarding._checklist', ['checklist' => $checklist])
    @endif

</div>
@endsection
