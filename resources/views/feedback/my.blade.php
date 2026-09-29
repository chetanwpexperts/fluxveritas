@extends('layouts.app')
@section('title', 'My Feedback')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">My Feedback</h1>
            <p class="page-subtitle">
                {{ $currentPeriod }} {{ $currentYear }} &middot; Your feedback and full org transparency
            </p>
        </div>
    </div>

    {{-- TRANSPARENCY BANNER --}}
    <div class="feedback-info-banner">
        <span class="feedback-info-icon">🏛️</span>
        <span>
            <strong>Full Transparency Policy.</strong>
            All performance feedback in your organization is visible to every team member.
            This ensures fairness and accountability at every level.
        </span>
    </div>

    {{-- SECTION 1: MY FEEDBACK --}}
    <div class="feedback-section-header">
        <h2 class="feedback-section-heading">My Feedback</h2>
        <span class="feedback-section-count">{{ $myFeedbacks->count() }} received</span>
    </div>

    @if($myFeedbacks->isEmpty())
    <div class="feedback-empty-inline">
        <p>No feedback submitted for you yet. Your manager will submit it at the end of each quarter.</p>
    </div>
    @else
    <div class="feedback-list mb-lg">
        @foreach($myFeedbacks as $feedback)
        <div class="feedback-list-card feedback-mine">
            <div class="feedback-list-header">
                <div>
                    <span class="feedback-period-tag">{{ $feedback->review_period }} {{ $feedback->review_year }}</span>
                    <span class="feedback-manager-by">by {{ $feedback->manager?->name }}</span>
                </div>
                <div class="feedback-list-right">
                    <span class="feedback-score-pill">{{ $feedback->implied_score }}%</span>
                    @if($feedback->statement)
                        <span class="feedback-badge-responded">✅ Responded</span>
                    @else
                        <span class="feedback-badge-pending">⏳ Add Statement</span>
                    @endif
                </div>
            </div>
            <div class="feedback-list-actions">
                <a href="{{ route('feedback.show', $feedback->id) }}" class="btn-primary btn-sm">View &amp; Respond</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- SECTION 2: ALL ORG FEEDBACK --}}
    <div class="feedback-section-header">
        <h2 class="feedback-section-heading">Organization Feedback</h2>
        <span class="feedback-section-count">{{ $orgFeedbacks->count() }} total</span>
    </div>

    @if($orgFeedbacks->isEmpty())
    <div class="feedback-empty-inline">
        <p>No other feedback submitted in your organization yet for this period.</p>
    </div>
    @else
    <div class="feedback-org-list">
        @foreach($orgFeedbacks as $feedback)
        <div class="feedback-org-card-simple">
            <div class="feedback-org-card-left">
                <div class="feedback-avatar-sm">
                    {{ strtoupper(substr($feedback->employee?->name ?? '?', 0, 1)) }}
                </div>
                <div class="feedback-org-details">
                    <div class="feedback-org-name">{{ $feedback->employee?->name }}</div>
                    <div class="feedback-org-role">{{ $feedback->employee?->job_title ?? 'Team Member' }}</div>
                    <div class="feedback-org-by">
                        Reviewed by {{ $feedback->manager?->name }} &middot; {{ $feedback->review_period }} {{ $feedback->review_year }}
                    </div>
                </div>
            </div>
            <div class="feedback-org-card-right">
                <div class="feedback-score-pill {{ $feedback->implied_score >= 75 ? 'score-pill-green' : ($feedback->implied_score >= 50 ? 'score-pill-amber' : 'score-pill-red') }}">
                    {{ $feedback->implied_score }}%
                </div>
                <div class="feedback-org-labels">
                    <span class="feedback-org-label">📦 {{ $feedback->deliveryLabel }}</span>
                    <span class="feedback-org-label">🤝 {{ $feedback->collaborationLabel }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
