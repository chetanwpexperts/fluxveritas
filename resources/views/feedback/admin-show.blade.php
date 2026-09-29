@extends('layouts.app')
@section('title', 'Feedback Detail — ' . $feedback->employee?->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Feedback Detail</h1>
            <p class="page-subtitle">
                {{ $feedback->employee?->name }}
                &middot; {{ $feedback->review_period }} {{ $feedback->review_year }}
                &middot; Manager: {{ $feedback->manager?->name }}
            </p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('feedback.admin') }}" class="btn-secondary">&larr; Back to Overview</a>
        </div>
    </div>

    {{-- BIAS ALERT --}}
    @if($biasFlag)
    <div class="feedback-bias-alert">
        <div class="feedback-bias-alert-icon">⚠️</div>
        <div class="feedback-bias-alert-body">
            <strong>Potential Bias Detected</strong>
            <p>The manager's implied score ({{ $managerImpliedScore }}%) deviates {{ $deviation }}% from the AI-calculated score ({{ $aiScore }}%). Review this feedback carefully.</p>
        </div>
    </div>
    @endif

    {{-- SCORE COMPARISON --}}
    <div class="feedback-comparison-grid">
        <div class="feedback-comparison-card">
            <div class="feedback-comparison-label">Manager Implied Score</div>
            <div class="feedback-comparison-value feedback-comparison-manager">{{ $managerImpliedScore }}%</div>
            <div class="feedback-comparison-sublabel">Based on 4 binary assessments</div>
        </div>
        @if($aiScore)
        <div class="feedback-comparison-divider">
            @if($deviation !== null)
            <div class="feedback-comparison-deviation {{ $biasFlag ? 'feedback-deviation-high' : 'feedback-deviation-ok' }}">
                {{ $deviation }}% gap
            </div>
            @endif
        </div>
        <div class="feedback-comparison-card">
            <div class="feedback-comparison-label">AI Calculated Score</div>
            <div class="feedback-comparison-value feedback-comparison-ai">{{ $aiScore }}%</div>
            <div class="feedback-comparison-sublabel">Based on objective work logs</div>
        </div>
        @endif
    </div>

    {{-- MANAGER ASSESSMENT --}}
    <div class="feedback-section">
        <h3 class="feedback-section-title">Section A — Manager Assessment</h3>
        <div class="feedback-scores-grid">
            <div class="feedback-score-card">
                <div class="feedback-score-card-icon">📦</div>
                <div class="feedback-score-card-label">Delivery</div>
                <div class="feedback-score-card-value">{{ $feedback->deliveryLabel }}</div>
            </div>
            <div class="feedback-score-card">
                <div class="feedback-score-card-icon">⏱️</div>
                <div class="feedback-score-card-label">Timeliness</div>
                <div class="feedback-score-card-value">{{ $feedback->timelinessLabel }}</div>
            </div>
            <div class="feedback-score-card">
                <div class="feedback-score-card-icon">📡</div>
                <div class="feedback-score-card-label">Availability</div>
                <div class="feedback-score-card-value">{{ $feedback->availabilityLabel }}</div>
            </div>
            <div class="feedback-score-card">
                <div class="feedback-score-card-icon">🤝</div>
                <div class="feedback-score-card-label">Collaboration</div>
                <div class="feedback-score-card-value">{{ $feedback->collaborationLabel }}</div>
            </div>
        </div>
    </div>

    {{-- CONTEXT NOTES --}}
    @if($feedback->notable_achievement || $feedback->area_of_improvement || $feedback->special_circumstances)
    <div class="feedback-section">
        <h3 class="feedback-section-title">Section B — Context Notes</h3>
        <div class="feedback-notes-grid">
            @if($feedback->notable_achievement)
            <div class="feedback-note-card feedback-note-positive">
                <div class="feedback-note-heading">⭐ Notable Achievement</div>
                <p class="feedback-note-body">{{ $feedback->notable_achievement }}</p>
            </div>
            @endif
            @if($feedback->area_of_improvement)
            <div class="feedback-note-card feedback-note-growth">
                <div class="feedback-note-heading">📈 Area for Growth</div>
                <p class="feedback-note-body">{{ $feedback->area_of_improvement }}</p>
            </div>
            @endif
            @if($feedback->special_circumstances)
            <div class="feedback-note-card feedback-note-context">
                <div class="feedback-note-heading">📎 Special Circumstances</div>
                <p class="feedback-note-body">{{ $feedback->special_circumstances }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- EMPLOYEE STATEMENT --}}
    <div class="feedback-section">
        <h3 class="feedback-section-title">Employee Statement</h3>
        @if($feedback->statement)
        <div class="feedback-statement-submitted">
            <div class="feedback-statement-submitted-header">
                <span class="feedback-badge-responded">✅ Statement Submitted</span>
                <span class="feedback-statement-date">{{ $feedback->statement->submitted_at?->format('M j, Y') }}</span>
            </div>
            <p class="feedback-statement-text">{{ $feedback->statement->statement }}</p>
        </div>
        @else
        <div class="feedback-no-statement-notice">
            <span class="feedback-no-statement">No statement submitted by the employee yet.</span>
        </div>
        @endif
    </div>

    {{-- METADATA --}}
    <div class="feedback-admin-meta">
        <div class="feedback-meta-row">
            <span class="feedback-meta-key">Submitted</span>
            <span class="feedback-meta-val">{{ $feedback->submitted_at?->format('M j, Y g:i A') ?? '—' }}</span>
        </div>
        <div class="feedback-meta-row">
            <span class="feedback-meta-key">Employee Viewed</span>
            <span class="feedback-meta-val">{{ $feedback->employee_viewed_at?->format('M j, Y g:i A') ?? 'Not yet viewed' }}</span>
        </div>
        <div class="feedback-meta-row">
            <span class="feedback-meta-key">Manager Confirmed</span>
            <span class="feedback-meta-val">{{ $feedback->manager_confirmed ? '✅ Yes' : '—' }}</span>
        </div>
        <div class="feedback-meta-row">
            <span class="feedback-meta-key">Status</span>
            <span class="feedback-meta-val">{{ ucfirst(str_replace('_', ' ', $feedback->status)) }}</span>
        </div>
    </div>

</div>
@endsection
