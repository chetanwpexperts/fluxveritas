@extends('layouts.app')
@section('title', 'Feedback Overview — Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Feedback Overview</h1>
            <p class="page-subtitle">{{ $period }} {{ $year }} &middot; Manager performance reviews</p>
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success">{{ session('success') }}</div>
    @endif

    {{-- STATS BAR --}}
    <div class="feedback-admin-stats">
        <div class="feedback-stat-card">
            <div class="feedback-stat-value">{{ $totalEmployees }}</div>
            <div class="feedback-stat-label">Total Employees</div>
        </div>
        <div class="feedback-stat-card">
            <div class="feedback-stat-value">{{ $submitted }}</div>
            <div class="feedback-stat-label">Feedbacks Submitted</div>
        </div>
        <div class="feedback-stat-card">
            <div class="feedback-stat-value">{{ $withStatement }}</div>
            <div class="feedback-stat-label">Employee Responses</div>
        </div>
        <div class="feedback-stat-card feedback-stat-warn">
            <div class="feedback-stat-value">{{ $pendingManagers }}</div>
            <div class="feedback-stat-label">Managers Yet to Submit</div>
        </div>
    </div>

    @if($feedbacks->isEmpty())
    <div class="feedback-empty">
        <div class="feedback-empty-icon">📋</div>
        <h3>No Feedbacks Submitted</h3>
        <p>No manager feedback has been submitted for {{ $period }} {{ $year }} yet.</p>
    </div>
    @else
    <div class="feedback-admin-table-wrapper">
        <table class="feedback-admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Manager</th>
                    <th>Implied Score</th>
                    <th>Status</th>
                    <th>Employee Response</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($feedbacks as $feedback)
                <tr class="feedback-admin-row">
                    <td>
                        <div class="feedback-admin-employee">
                            <div class="feedback-avatar feedback-avatar-sm">
                                {{ strtoupper(substr($feedback->employee?->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <div class="feedback-admin-name">{{ $feedback->employee?->name }}</div>
                                <div class="feedback-admin-role">{{ $feedback->employee?->job_title ?? 'Team Member' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="feedback-admin-manager">{{ $feedback->manager?->name }}</td>
                    <td>
                        <span class="feedback-admin-score">{{ $feedback->implied_score }}%</span>
                    </td>
                    <td>
                        @if($feedback->status === 'submitted' || $feedback->status === 'employee_reviewed')
                            <span class="feedback-status-pill status-pill-submitted">Submitted</span>
                        @elseif($feedback->status === 'draft')
                            <span class="feedback-status-pill status-pill-draft">Draft</span>
                        @else
                            <span class="feedback-status-pill status-pill-pending">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($feedback->statement)
                            <span class="feedback-has-statement">💬 Responded</span>
                        @else
                            <span class="feedback-no-statement">—</span>
                        @endif
                    </td>
                    <td class="feedback-admin-date">
                        {{ $feedback->submitted_at?->format('M j') ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('feedback.admin.show', $feedback->id) }}" class="btn-secondary btn-sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
