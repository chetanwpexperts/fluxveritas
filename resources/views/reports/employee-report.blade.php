@extends('layouts.app')

@section('title', 'Employee Report — ' . $period . ' ' . $year)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/performance.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-container">

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $report['employee']->name }}</h1>
            <p class="page-subtitle">
                {{ $report['designation_label'] }}
                @if($report['department'])— {{ $report['department'] }}@endif
            </p>
        </div>
        <div class="perf-period-selector">
            <form method="GET" action="{{ request()->url() }}" class="period-form">
                <select name="period" class="perf-select" onchange="this.form.submit()">
                    @foreach(['Q1','Q2','Q3','Q4'] as $q)
                    <option value="{{ $q }}" @selected($period === $q)>{{ $q }}</option>
                    @endforeach
                </select>
                <select name="year" class="perf-select" onchange="this.form.submit()">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    <div class="perf-two-col">

        {{-- AI Score Circle --}}
        <div class="perf-score-block">
            @if($report['ai_score'])
            <div class="perf-score-circle {{ $report['ai_score'] >= $report['seniority_benchmark'] ? 'score-green' : ($report['ai_score'] >= $report['seniority_benchmark'] - 10 ? 'score-amber' : 'score-red') }}">
                <span class="perf-score-num">{{ $report['ai_score'] }}</span>
                <span class="perf-score-out">/100</span>
            </div>
            <div class="perf-score-label">AI Performance Score</div>
            <div class="perf-benchmark-bar">
                <div class="perf-benchmark-track">
                    <div class="perf-benchmark-fill" data-pct="{{ min(100, $report['ai_score']) }}"></div>
                    <div class="perf-benchmark-line" data-pct="{{ $report['seniority_benchmark'] }}" title="Benchmark: {{ $report['seniority_benchmark'] }}"></div>
                </div>
                <div class="perf-benchmark-hint">Benchmark for {{ strtolower($report['employee']->seniority_level ?? 'mid') }}: {{ $report['seniority_benchmark'] }}/100</div>
            </div>
            @else
            <div class="perf-no-score">No AI score available for this period</div>
            @endif

            @if($report['peer_avg'])
            <div class="perf-peer-score">
                <span class="perf-peer-label">Peer Score</span>
                <span class="perf-peer-val">{{ $report['peer_avg'] }}/100</span>
                <span class="perf-peer-count">({{ $report['peer_count'] }} peers)</span>
            </div>
            @endif
        </div>

        {{-- Work Summary --}}
        <div class="perf-work-summary">
            <div class="perf-card-title">Work Summary — {{ $period }} {{ $year }}</div>
            <div class="perf-criteria-list">
                <div class="perf-criteria-row">
                    <span class="perf-criteria-label">Consistency</span>
                    <div class="perf-bar-wrap">
                        <div class="perf-bar" data-pct="{{ $report['consistency'] }}"></div>
                    </div>
                    <span class="perf-criteria-val">{{ $report['consistency'] }}%</span>
                </div>
                <div class="perf-criteria-row">
                    <span class="perf-criteria-label">Logged Days</span>
                    <div class="perf-bar-wrap">
                        <div class="perf-bar" data-pct="{{ $report['working_days'] > 0 ? round(($report['logged_days'] / $report['working_days']) * 100) : 0 }}"></div>
                    </div>
                    <span class="perf-criteria-val">{{ $report['logged_days'] }} / {{ $report['working_days'] }}</span>
                </div>
                @if($report['avg_quality'] > 0)
                <div class="perf-criteria-row">
                    <span class="perf-criteria-label">Avg Output Quality</span>
                    <div class="perf-bar-wrap">
                        <div class="perf-bar" data-pct="{{ $report['avg_quality'] * 10 }}"></div>
                    </div>
                    <span class="perf-criteria-val">{{ $report['avg_quality'] }}/10</span>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Stats Grid --}}
    <div class="ceo-stats-grid">
        <div class="ceo-stat">
            <div class="ceo-stat-num">{{ $report['total_hours'] }}h</div>
            <div class="ceo-stat-label">Hours Logged</div>
        </div>
        <div class="ceo-stat">
            <div class="ceo-stat-num">{{ $report['tasks_completed'] }} / {{ $report['total_tasks'] }}</div>
            <div class="ceo-stat-label">Tasks Completed</div>
        </div>
        <div class="ceo-stat">
            <div class="ceo-stat-num">{{ $report['blockers_resolved'] }}</div>
            <div class="ceo-stat-label">Blockers Resolved</div>
        </div>
        @if($report['is_tech'] && $report['github_commits'] !== null)
        <div class="ceo-stat">
            <div class="ceo-stat-num">{{ $report['github_commits'] }}</div>
            <div class="ceo-stat-label">GitHub Commits</div>
        </div>
        @endif
    </div>

    {{-- Manager Feedback --}}
    @if($report['feedback'])
    <div class="perf-feedback-block">
        <div class="perf-card-title">Manager Feedback — {{ $period }} {{ $year }}</div>
        <div class="perf-feedback-grid">
            <div class="perf-fb-card">
                <div class="perf-fb-label">Delivery</div>
                <div class="perf-fb-val">{{ $report['feedback']->deliveryLabel }}</div>
            </div>
            <div class="perf-fb-card">
                <div class="perf-fb-label">Timeliness</div>
                <div class="perf-fb-val">{{ $report['feedback']->timelinessLabel }}</div>
            </div>
            <div class="perf-fb-card">
                <div class="perf-fb-label">Availability</div>
                <div class="perf-fb-val">{{ $report['feedback']->availabilityLabel }}</div>
            </div>
            <div class="perf-fb-card">
                <div class="perf-fb-label">Collaboration</div>
                <div class="perf-fb-val">{{ $report['feedback']->collaborationLabel }}</div>
            </div>
        </div>
        @if($report['feedback']->context_notes)
        <div class="perf-notes">{{ $report['feedback']->context_notes }}</div>
        @endif
        <div class="perf-fb-meta">
            Reviewed by {{ $report['feedback']->manager?->name ?? 'Manager' }}
            · {{ $report['feedback']->updated_at->format('M j, Y') }}
        </div>
    </div>
    @else
    <div class="perf-empty-feedback">No manager feedback submitted for {{ $period }} {{ $year }}</div>
    @endif

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/performance.js') }}" defer></script>
@endpush
