@extends('layouts.app')
@section('title', 'Team Reports')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
    <link rel="stylesheet" href="{{ asset('css/performance.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Team Reports</h1>
            <p class="page-subtitle">
                {{ $period }} {{ $year }} ·
                {{ count($reports) }} employees
            </p>
        </div>
        <div class="page-header-right">
            <div class="team-report-period-selector">
                @foreach(['Q1','Q2','Q3','Q4'] as $q)
                <a href="?period={{ $q }}&year={{ $year }}"
                    class="trps-btn {{ $period === $q ? 'trps-active' : '' }}">
                    {{ $q }}
                </a>
                @endforeach
                <a href="?period={{ $period }}&year={{ now()->year - 1 }}"
                    class="trps-btn {{ $year == now()->year - 1 ? 'trps-active' : '' }}">
                    {{ now()->year - 1 }}
                </a>
                <a href="?period={{ $period }}&year={{ now()->year }}"
                    class="trps-btn {{ $year == now()->year ? 'trps-active' : '' }}">
                    {{ now()->year }}
                </a>
            </div>
        </div>
    </div>

    {{-- SUMMARY STATS --}}
    <div class="tr-summary-row">
        @php
            $avgScore = collect($reports)
                ->filter(fn($r) => $r['ai_score'])
                ->avg('ai_score');
            $totalHours = collect($reports)->sum('total_hours');
            $totalTasks = collect($reports)->sum('tasks_completed');
            $avgConsistency = collect($reports)->avg('consistency');
        @endphp
        <div class="tr-summary-card">
            <div class="tr-summary-value">
                {{ $avgScore ? round($avgScore, 1) . '%' : '—' }}
            </div>
            <div class="tr-summary-label">Avg AI Score</div>
        </div>
        <div class="tr-summary-card">
            <div class="tr-summary-value">{{ round($avgConsistency) }}%</div>
            <div class="tr-summary-label">Avg Consistency</div>
        </div>
        <div class="tr-summary-card">
            <div class="tr-summary-value">{{ $totalHours }}h</div>
            <div class="tr-summary-label">Total Hours Logged</div>
        </div>
        <div class="tr-summary-card">
            <div class="tr-summary-value">{{ $totalTasks }}</div>
            <div class="tr-summary-label">Tasks Completed</div>
        </div>
    </div>

    {{-- TEAM TABLE --}}
    <div class="tr-table-card">
        <table class="tr-table">
            <thead>
                <tr>
                    <th class="tr-th">Employee</th>
                    <th class="tr-th">Department</th>
                    <th class="tr-th tr-center">Consistency</th>
                    <th class="tr-th tr-center">Hours</th>
                    <th class="tr-th tr-center">Tasks</th>
                    <th class="tr-th tr-center">AI Score</th>
                    <th class="tr-th tr-center">Peer Score</th>
                    <th class="tr-th tr-center">Feedback</th>
                    <th class="tr-th tr-center">Report</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                @php
                    $score = $r['ai_score'];
                    $scoreClass = $score
                        ? ($score >= $r['seniority_benchmark']
                            ? 'tr-score-green'
                            : ($score >= $r['seniority_benchmark'] * 0.8
                                ? 'tr-score-amber'
                                : 'tr-score-red'))
                        : 'tr-score-none';
                    $consistencyClass = $r['consistency'] >= 80
                        ? 'tr-metric-green'
                        : ($r['consistency'] >= 60
                            ? 'tr-metric-amber'
                            : 'tr-metric-red');
                @endphp
                <tr class="tr-row">
                    {{-- EMPLOYEE --}}
                    <td class="tr-td">
                        <div class="tr-employee">
                            <div class="tr-avatar">
                                {{ strtoupper(substr($r['employee']->name, 0, 1)) }}
                            </div>
                            <div class="tr-employee-info">
                                <div class="tr-employee-name">
                                    {{ $r['employee']->name }}
                                </div>
                                <div class="tr-employee-title">
                                    {{ $r['designation_label'] ?: 'Team Member' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- DEPARTMENT --}}
                    <td class="tr-td">
                        <span class="tr-dept-badge">
                            {{ $r['department'] ?? '—' }}
                        </span>
                    </td>

                    {{-- CONSISTENCY --}}
                    <td class="tr-td tr-center">
                        <div class="tr-metric-wrap">
                            <span class="tr-metric {{ $consistencyClass }}">
                                {{ $r['consistency'] }}%
                            </span>
                            <div class="tr-mini-bar">
                                <div class="tr-mini-fill {{ $consistencyClass }}"
                                    style="width:{{ $r['consistency'] }}%">
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- HOURS --}}
                    <td class="tr-td tr-center">
                        <span class="tr-hours">
                            {{ $r['total_hours'] }}h
                        </span>
                        <div class="tr-hours-sub">
                            {{ $r['logged_days'] }}/{{ $r['working_days'] }} days
                        </div>
                    </td>

                    {{-- TASKS --}}
                    <td class="tr-td tr-center">
                        <span class="tr-tasks">
                            {{ $r['tasks_completed'] }}
                        </span>
                        <div class="tr-hours-sub">
                            of {{ $r['total_tasks'] }}
                        </div>
                    </td>

                    {{-- AI SCORE --}}
                    <td class="tr-td tr-center">
                        <span class="tr-score-pill {{ $scoreClass }}">
                            {{ $score ? $score . '%' : 'N/A' }}
                        </span>
                        @if($score)
                        <div class="tr-hours-sub">
                            target {{ $r['seniority_benchmark'] }}%
                        </div>
                        @endif
                    </td>

                    {{-- PEER SCORE --}}
                    <td class="tr-td tr-center">
                        @if($r['peer_avg'])
                        <span class="tr-score-pill tr-score-green">
                            {{ $r['peer_avg'] }}%
                        </span>
                        <div class="tr-hours-sub">
                            {{ $r['peer_count'] }} peers
                        </div>
                        @else
                        <span class="tr-na">—</span>
                        @endif
                    </td>

                    {{-- FEEDBACK --}}
                    <td class="tr-td tr-center">
                        @if($r['feedback'])
                        <span class="tr-feedback-badge tr-feedback-done">
                            ✅ Submitted
                        </span>
                        <div class="tr-hours-sub">
                            {{ $r['feedback']->implied_score }}% implied
                        </div>
                        @else
                        <span class="tr-feedback-badge tr-feedback-pending">
                            ⏳ Pending
                        </span>
                        @endif
                    </td>

                    {{-- VIEW --}}
                    <td class="tr-td tr-center">
                        <a href="{{ route('reports.employee', $r['employee']->id) }}?period={{ $period }}&year={{ $year }}"
                            class="tr-view-btn">
                            View →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="tr-empty">
                        No employees found for this period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
