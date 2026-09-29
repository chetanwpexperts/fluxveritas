@extends('layouts.app')

@section('title', 'Onboarding — ' . $checklist->employee->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hr-onboarding.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="dir-back">
        <a href="{{ route('hr-onboarding.index') }}" class="dir-back-link">← Back to Onboarding</a>
    </div>

    <div class="onb-show-header">
        <div>
            <h1 class="onb-title">{{ $checklist->employee->name }}'s Onboarding</h1>
            <p class="onb-subtitle">Created by {{ $checklist->creator->name }} on {{ $checklist->created_at->format('M d, Y') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="onb-alert">{{ session('success') }}</div>
    @endif

    @include('hr-onboarding._checklist', ['checklist' => $checklist])

    @if(auth()->user()->hasAnyRole(['hr','admin','owner','super_admin']))
    <div class="onb-add-task-section">
        <h3 class="onb-section-title">Add Task</h3>
        <form method="POST" action="{{ route('hr-onboarding.tasks.add', $checklist) }}" class="onb-add-task-form">
            @csrf
            <input type="text" name="title" class="dir-form-input" placeholder="Task title..." required style="flex:1;">
            <button type="submit" class="dir-form-btn">Add</button>
        </form>
    </div>
    @endif

</div>
@endsection
