@extends('layouts.app')
@section('title', 'My Feedback — ' . $feedback->review_period . ' ' . $feedback->review_year)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Performance Feedback</h1>
            <p class="page-subtitle">
                {{ $feedback->review_period }} {{ $feedback->review_year }}
                &middot; From {{ $feedback->manager?->name }}
                &middot; Submitted {{ $feedback->submitted_at?->format('M j, Y') }}
            </p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('feedback.my') }}" class="btn-secondary">&larr; Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success">{{ session('success') }}</div>
    @endif

    {{-- SCORE OVERVIEW --}}
    <div class="feedback-show-overview">
        <div class="feedback-show-total">
            <div class="feedback-show-total-label">Overall Assessment</div>
            <div class="feedback-show-total-value">{{ $feedback->implied_score }}%</div>
            <div class="feedback-show-total-sublabel">Manager implied score</div>
        </div>
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
        <h3 class="feedback-section-title">Manager's Notes</h3>
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
    <div class="feedback-statement-section">
        <h3 class="feedback-section-title">Your Statement</h3>

        @if($hasStatement)
        <div class="feedback-statement-submitted">
            <div class="feedback-statement-submitted-header">
                <span class="feedback-badge-responded">✅ Statement Submitted</span>
                <span class="feedback-statement-date">{{ $feedback->statement->submitted_at?->format('M j, Y') }}</span>
            </div>
            <p class="feedback-statement-text">{{ $feedback->statement->statement }}</p>
        </div>
        @else
        <div class="feedback-statement-prompt">
            <p class="feedback-statement-intro">
                You have the opportunity to add context or a professional response to this feedback.
                Your statement will be visible to the CEO alongside the manager's assessment.
            </p>
            @if($errors->any())
            <div class="fv-alert fv-alert-error">
                <ul class="feedback-error-list">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif
            <form method="POST" action="{{ route('feedback.statement', $feedback->id) }}">
                @csrf
                <div class="form-group">
                    <label class="fv-label">
                        Your professional statement
                        <span class="feedback-char-hint">(20–1000 characters)</span>
                    </label>
                    <textarea name="statement" class="fv-input" rows="6" minlength="20" maxlength="1000"
                        placeholder="Add context, acknowledge achievements, or provide your perspective on this review period...">{{ old('statement') }}</textarea>
                    @error('statement')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="feedback-actions">
                    <button type="submit" class="btn-primary">Submit Statement &rarr;</button>
                </div>
            </form>
        </div>
        @endif
    </div>

</div>
@endsection
