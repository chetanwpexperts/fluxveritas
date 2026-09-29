@extends('layouts.app')
@section('title', 'Bias Detection Reports')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Bias Detection Reports</h1>
            <p class="page-subtitle">{{ $period }} {{ $year }} &middot; AI-powered fairness analysis</p>
        </div>
    </div>

    {{-- FLAGGED FEEDBACK --}}
    <div class="feedback-section-header">
        <h2 class="feedback-section-heading">Flagged Feedback</h2>
        <span class="feedback-section-count">
            {{ $biasReports->where('bias_detected', true)->count() }} flagged
        </span>
    </div>

    @if($biasReports->isEmpty())
    <div class="feedback-empty-inline mb-lg">
        <p>No bias reports generated yet. Submit manager feedback to trigger analysis.</p>
    </div>
    @else
    <div class="feedback-org-list mb-lg">
        @foreach($biasReports as $report)
        <div class="bias-report-card {{ $report->bias_detected ? 'bias-card-flagged' : 'bias-card-clean' }}">
            <div class="bias-report-left">
                <div class="bias-report-avatar">
                    {{ strtoupper(substr($report->employee?->name ?? '?', 0, 1)) }}
                </div>
                <div class="bias-report-info">
                    <div class="bias-report-name">{{ $report->employee?->name }}</div>
                    <div class="bias-report-meta">Reviewed by {{ $report->manager?->name }}</div>
                    @if($report->bias_reason)
                    <div class="bias-report-reason">{{ $report->bias_reason }}</div>
                    @endif
                </div>
            </div>
            <div class="bias-report-right">
                <div class="bias-scores">
                    <div class="bias-score-item">
                        <span class="bias-score-label">AI Score</span>
                        <span class="bias-score-value">{{ $report->ai_score ? round($report->ai_score) . '%' : 'N/A' }}</span>
                    </div>
                    <div class="bias-score-item">
                        <span class="bias-score-label">Manager</span>
                        <span class="bias-score-value">{{ round($report->manager_implied_score) }}%</span>
                    </div>
                    @if($report->peer_score)
                    <div class="bias-score-item">
                        <span class="bias-score-label">Peers</span>
                        <span class="bias-score-value">{{ round($report->peer_score) }}%</span>
                    </div>
                    @endif
                </div>
                <div class="bias-confidence {{ $report->bias_detected ? 'bias-high' : 'bias-low' }}">
                    {{ $report->bias_detected ? '⚠️' : '✅' }} {{ $report->bias_confidence }}% confidence
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- MANAGER ACCOUNTABILITY --}}
    <div class="feedback-section-header">
        <h2 class="feedback-section-heading">Manager Accountability Scores</h2>
    </div>

    @if($managerScores->isEmpty())
    <div class="feedback-empty-inline">
        <p>No manager accountability data yet.</p>
    </div>
    @else
    <div class="feedback-org-list">
        @foreach($managerScores as $score)
        <div class="accountability-card">
            <div class="accountability-left">
                <div class="feedback-avatar-sm">
                    {{ strtoupper(substr($score->manager?->name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="feedback-org-name">{{ $score->manager?->name }}</div>
                    <div class="accountability-meta">
                        {{ $score->feedbacks_submitted }}/{{ $score->feedbacks_due }} submitted
                        &middot; {{ $score->bias_flags }} bias flag(s)
                    </div>
                </div>
            </div>
            <div class="feedback-score-pill {{ $score->accountability_score >= 80 ? 'score-pill-green' : ($score->accountability_score >= 60 ? 'score-pill-amber' : 'score-pill-red') }}">
                {{ round($score->accountability_score) }}%
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
