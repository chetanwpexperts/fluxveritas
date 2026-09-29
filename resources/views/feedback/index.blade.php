@extends('layouts.app')
@section('title', 'Team Feedback')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Team Feedback</h1>
            <p class="page-subtitle">
                {{ $currentPeriod }} {{ $currentYear }} &middot; Performance reviews for your direct reports
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="fv-alert fv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="fv-alert fv-alert-info">{{ session('info') }}</div>
    @endif

    @if($teamMembers->isEmpty())
    <div class="feedback-empty">
        <div class="feedback-empty-icon">👥</div>
        <h3>No Direct Reports</h3>
        <p>You don't have any direct reports assigned. Contact your admin to set up your team hierarchy.</p>
    </div>
    @else

    <div class="feedback-period-banner">
        <span class="feedback-period-label">📅 Current Period: {{ $currentPeriod }} {{ $currentYear }}</span>
        <span class="feedback-period-deadline">Deadline: {{ now()->endOfQuarter()->format('M j, Y') }}</span>
    </div>

    <div class="feedback-team-grid">
        @foreach($teamMembers as $employee)
        @php
            $existing = $existingFeedbacks[$employee->id] ?? null;
            $status   = $existing?->status ?? 'pending';
        @endphp
        <div class="feedback-team-card feedback-status-{{ $status }}">
            <div class="feedback-team-card-header">
                <div class="feedback-avatar">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>
                <div class="feedback-employee-info">
                    <div class="feedback-employee-name">{{ $employee->name }}</div>
                    <div class="feedback-employee-title">{{ $employee->job_title ?? 'Team Member' }}</div>
                </div>
                <div class="feedback-status-badge status-badge-{{ $status }}">
                    @if($status === 'submitted' || $status === 'employee_reviewed')
                        ✅ Submitted
                    @elseif($status === 'draft')
                        ✏️ Draft
                    @else
                        ⏳ Pending
                    @endif
                </div>
            </div>

            <div class="feedback-team-card-body">
                @if($existing && $status !== 'pending')
                <div class="feedback-score-preview">
                    <span class="feedback-score-preview-label">Implied Score:</span>
                    <span class="feedback-score-preview-value">{{ $existing->implied_score }}%</span>
                </div>
                @endif
                @if($existing?->statement)
                <div class="feedback-has-statement">💬 Employee added statement</div>
                @endif
            </div>

            <div class="feedback-team-card-footer">
                <a href="{{ route('feedback.create', $employee->id) }}" class="btn-primary btn-sm">
                    {{ $existing ? 'Edit Feedback' : 'Give Feedback' }}
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
