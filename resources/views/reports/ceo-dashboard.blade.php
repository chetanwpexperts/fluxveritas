@extends('layouts.app')

@section('title', 'CEO Dashboard — Daily Digest')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-container">

    <div class="page-header">
        <div>
            <h1 class="page-title">CEO Dashboard</h1>
            <p class="page-subtitle">{{ $data['date'] }}</p>
        </div>
        <form method="POST" action="{{ route('reports.ceo.send-now') }}">
            @csrf
            <button type="submit" class="sa-btn-primary">Send Digest Email Now</button>
        </form>
    </div>

    @if(session('success'))
    <div class="alert-success mb-md">{{ session('success') }}</div>
    @endif

    {{-- Health Card --}}
    <div class="ceo-health-card ceo-health-{{ $data['status'] }}">
        <div class="ceo-health-left">
            <div class="ceo-health-score">{{ $data['health_score'] }}</div>
            <div class="ceo-health-label">Team Health Score</div>
        </div>
        <div class="ceo-health-stats">
            <div class="ceo-health-stat">
                <span class="ceo-health-num">{{ $data['logged_today'] }}/{{ $data['total_employees'] }}</span>
                <span class="ceo-health-desc">Logged Today</span>
            </div>
            <div class="ceo-health-stat">
                <span class="ceo-health-num">{{ $data['tasks_completed'] }}</span>
                <span class="ceo-health-desc">Tasks Done Today</span>
            </div>
            <div class="ceo-health-stat">
                <span class="ceo-health-num">{{ $data['active_sprints'] }}</span>
                <span class="ceo-health-desc">Active Sprints</span>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="ceo-stats-grid">
        <div class="ceo-stat {{ $data['open_blockers'] > 0 ? 'stat-warn' : '' }}">
            <div class="ceo-stat-num">{{ $data['open_blockers'] }}</div>
            <div class="ceo-stat-label">Open Blockers</div>
        </div>
        <div class="ceo-stat {{ $data['critical_blockers'] > 0 ? 'stat-critical' : '' }}">
            <div class="ceo-stat-num">{{ $data['critical_blockers'] }}</div>
            <div class="ceo-stat-label">Escalated Blockers</div>
        </div>
        <div class="ceo-stat {{ $data['fairness_flags'] > 0 ? 'stat-warn' : '' }}">
            <div class="ceo-stat-num">{{ $data['fairness_flags'] }}</div>
            <div class="ceo-stat-label">Fairness Flags</div>
        </div>
        <div class="ceo-stat {{ $data['bias_reports'] > 0 ? 'stat-warn' : '' }}">
            <div class="ceo-stat-num">{{ $data['bias_reports'] }}</div>
            <div class="ceo-stat-label">Bias Reports</div>
        </div>
        <div class="ceo-stat {{ $data['overloaded_count'] > 0 ? 'stat-warn' : '' }}">
            <div class="ceo-stat-num">{{ $data['overloaded_count'] }}</div>
            <div class="ceo-stat-label">Overloaded</div>
        </div>
        <div class="ceo-stat">
            <div class="ceo-stat-num">{{ $data['total_employees'] }}</div>
            <div class="ceo-stat-label">Total Employees</div>
        </div>
    </div>

    <div class="ceo-two-col">

        {{-- Alerts --}}
        <div class="ceo-alerts-section">
            <div class="ceo-section-title">Alerts</div>
            @forelse($data['alerts'] as $alert)
            <div class="ceo-alert-card ceo-alert-{{ $alert['type'] }}">
                <span class="ceo-alert-icon">{{ $alert['icon'] }}</span>
                <div class="ceo-alert-body">
                    <span class="ceo-alert-msg">{{ $alert['message'] }}</span>
                    <a href="{{ $alert['url'] }}" class="ceo-alert-link">View</a>
                </div>
            </div>
            @empty
            <div class="ceo-alert-empty">No critical alerts today</div>
            @endforelse
        </div>

        {{-- Not Logged --}}
        <div class="ceo-not-logged">
            <div class="ceo-section-title">Not Logged Today
                @if($data['not_logged_count'] > 0)
                <span class="ceo-badge-warn">{{ $data['not_logged_count'] }}</span>
                @endif
            </div>
            @if(empty($data['not_logged_names']))
            <div class="ceo-all-logged">Everyone logged in today!</div>
            @else
            <div class="ceo-chips">
                @foreach($data['not_logged_names'] as $name)
                <span class="ceo-member-chip">{{ $name }}</span>
                @endforeach
                @if($data['not_logged_count'] > 5)
                <span class="ceo-member-chip ceo-chip-more">+{{ $data['not_logged_count'] - 5 }} more</span>
                @endif
            </div>
            @endif
        </div>

    </div>

    {{-- Quick Links --}}
    <div class="ceo-section-title">Quick Actions</div>
    <div class="ceo-links-grid">
        <a href="{{ route('ceo.index') }}" class="ceo-link-card">
            <span class="ceo-link-icon">📊</span>
            <span class="ceo-link-label">Command Center</span>
        </a>
        <a href="{{ route('dependency.index') }}" class="ceo-link-card">
            <span class="ceo-link-icon">🚧</span>
            <span class="ceo-link-label">Blockers</span>
        </a>
        <a href="{{ route('fairness.index') }}" class="ceo-link-card">
            <span class="ceo-link-icon">⚖️</span>
            <span class="ceo-link-label">Fairness Engine</span>
        </a>
        <a href="{{ route('feedback.admin') }}" class="ceo-link-card">
            <span class="ceo-link-icon">📋</span>
            <span class="ceo-link-label">Feedback Overview</span>
        </a>
        <a href="{{ route('reports.team') }}" class="ceo-link-card">
            <span class="ceo-link-icon">👥</span>
            <span class="ceo-link-label">Team Reports</span>
        </a>
        <a href="{{ route('feedback.bias-reports') }}" class="ceo-link-card">
            <span class="ceo-link-icon">⚠️</span>
            <span class="ceo-link-label">Bias Reports</span>
        </a>
    </div>

</div>
@endsection
