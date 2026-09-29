@extends('layouts.app')
@section('title', 'Dashboard')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/unified-dashboard.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

{{-- GREETING HEADER --}}
<div class="ud-greeting">
    <div class="ud-greeting-left">
        <h1 class="ud-greeting-title">
            {{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
            {{ auth()->user()->name }} 👋
        </h1>
        <p class="ud-greeting-sub">
            {{ now()->format('l, F j, Y') }} · Organization Control Panel
        </p>
    </div>
    <div class="ud-greeting-right">
        <div class="ud-health-badge ud-health-{{ $healthStatus }}">
            <span class="ud-health-dot"></span>
            {{ $healthScore }}/100 Health Score
        </div>
    </div>
</div>

{{-- PULSE STATS --}}
<div class="ud-pulse-grid">
    <div class="ud-pulse-card">
        <div class="ud-pulse-icon">👥</div>
        <div class="ud-pulse-value">{{ $loggedToday }}/{{ $totalUsers }}</div>
        <div class="ud-pulse-label">Logged Today</div>
        <div class="ud-pulse-bar">
            <div class="ud-pulse-fill ud-fill-{{ $healthStatus }}"
                style="width:{{ $totalUsers > 0 ? round(($loggedToday/$totalUsers)*100) : 0 }}%">
            </div>
        </div>
    </div>
    <div class="ud-pulse-card {{ $openBlockers > 0 ? 'ud-pulse-warn' : '' }}">
        <div class="ud-pulse-icon">🚫</div>
        <div class="ud-pulse-value">{{ $openBlockers }}</div>
        <div class="ud-pulse-label">Open Blockers</div>
        @if($escalatedBlockers > 0)
        <div class="ud-pulse-sub ud-text-red">{{ $escalatedBlockers }} escalated</div>
        @endif
    </div>
    <div class="ud-pulse-card">
        <div class="ud-pulse-icon">🚀</div>
        <div class="ud-pulse-value">{{ $activeSprints }}</div>
        <div class="ud-pulse-label">Active Sprints</div>
    </div>
    <div class="ud-pulse-card {{ $fairnessFlags > 0 ? 'ud-pulse-warn' : '' }}">
        <div class="ud-pulse-icon">⚖️</div>
        <div class="ud-pulse-value">{{ $fairnessFlags }}</div>
        <div class="ud-pulse-label">Fairness Flags</div>
    </div>
    <div class="ud-pulse-card {{ $biasReports > 0 ? 'ud-pulse-alert' : '' }}">
        <div class="ud-pulse-icon">⚠️</div>
        <div class="ud-pulse-value">{{ $biasReports }}</div>
        <div class="ud-pulse-label">Bias Reports</div>
    </div>
    <div class="ud-pulse-card {{ $pendingFeedback > 0 ? 'ud-pulse-warn' : '' }}">
        <div class="ud-pulse-icon">📋</div>
        <div class="ud-pulse-value">{{ $pendingFeedback }}</div>
        <div class="ud-pulse-label">Pending Feedback</div>
    </div>
</div>

{{-- ALERTS + NOT LOGGED --}}
@if(!empty($alerts) || $notLoggedUsers->count() > 0)
<div class="ud-middle-grid">

    @if(!empty($alerts))
    <div class="ud-alerts-card">
        <div class="ud-card-header">
            <h3 class="ud-card-title">⚡ Needs Attention</h3>
            <span class="ud-badge-count">{{ count($alerts) }}</span>
        </div>
        <div class="ud-alerts-list">
            @foreach($alerts as $alert)
            <div class="ud-alert-row ud-alert-{{ $alert['type'] }}">
                <span class="ud-alert-icon">{{ $alert['icon'] }}</span>
                <span class="ud-alert-msg">{{ $alert['message'] }}</span>
                <a href="{{ $alert['url'] }}" class="ud-alert-action">{{ $alert['label'] }} →</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($notLoggedUsers->count() > 0)
    <div class="ud-notlogged-card">
        <div class="ud-card-header">
            <h3 class="ud-card-title">📝 Not Logged Today</h3>
            <span class="ud-badge-count ud-badge-red">{{ $notLoggedCount }}</span>
        </div>
        <div class="ud-member-chips">
            @foreach($notLoggedUsers as $u)
            <span class="ud-member-chip">
                <span class="ud-chip-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                {{ $u->name }}
            </span>
            @endforeach
            @if($notLoggedCount > 8)
            <span class="ud-member-chip ud-chip-more">+{{ $notLoggedCount - 8 }} more</span>
            @endif
        </div>
        <a href="{{ route('team.index') }}" class="ud-view-all-link">View full team →</a>
    </div>
    @endif

</div>
@endif

{{-- MODULE CARDS --}}
<div class="ud-modules-section">

    {{-- TEAM MANAGEMENT --}}
    <div class="ud-module-group">
        <div class="ud-module-group-label">Team Management</div>
        <div class="ud-modules-grid">
            <a href="{{ route('team.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-blue">👥</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Team</div>
                    <div class="ud-module-stat">{{ $totalUsers }} members · {{ $loggedToday }} active today</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('org-chart.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-purple">🌳</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Org Chart</div>
                    <div class="ud-module-stat">Hierarchy & reporting structure</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('departments.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-green">🏢</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Departments</div>
                    <div class="ud-module-stat">Manage org structure</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('settings.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-slate">⚙️</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Settings</div>
                    <div class="ud-module-stat">Users, roles, permissions, designations</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('announcements.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-amber">📢</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Announcements</div>
                    <div class="ud-module-stat">Company updates and notices</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('leaves.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-blue">🏖️</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Leave Management</div>
                    <div class="ud-module-stat">{{ \App\Models\LeaveApplication::forOrg(auth()->user()->organization_id)->where('status','pending')->count() }} pending request(s)</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
        </div>
    </div>

    {{-- WORK & INTELLIGENCE --}}
    <div class="ud-module-group">
        <div class="ud-module-group-label">Work & Intelligence</div>
        <div class="ud-modules-grid">
            <a href="{{ route('tasks.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-blue">✅</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Tasks</div>
                    <div class="ud-module-stat">{{ $activeTasks }} active tasks</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('sprints.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-green">🚀</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Sprints</div>
                    <div class="ud-module-stat">{{ $activeSprints }} active sprint(s)</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('dependency.index') }}"
                class="ud-module-card {{ $escalatedBlockers > 0 ? 'ud-card-alert' : ($openBlockers > 0 ? 'ud-card-warn' : '') }}">
                <div class="ud-module-icon ud-icon-red">🚫</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Blockers</div>
                    <div class="ud-module-stat">
                        {{ $openBlockers }} open
                        @if($escalatedBlockers > 0)
                        · <span class="ud-text-red">{{ $escalatedBlockers }} escalated</span>
                        @endif
                    </div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            @if(auth()->user()->hasAnyRole(['ceo', 'super_admin']))
            <a href="{{ route('ceo.index') }}" class="ud-module-card ud-card-feature">
                <div class="ud-module-icon ud-icon-dark">🖥️</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Command Center</div>
                    <div class="ud-module-stat">Full team output view</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            @endif
        </div>
    </div>

    {{-- PERFORMANCE & FAIRNESS --}}
    <div class="ud-module-group">
        <div class="ud-module-group-label">Performance & Fairness</div>
        <div class="ud-modules-grid">
            <a href="{{ route('reports.team') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-blue">📊</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Team Reports</div>
                    <div class="ud-module-stat">{{ $period }} {{ now()->year }} performance</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('fairness.index') }}"
                class="ud-module-card {{ $fairnessFlags > 0 ? 'ud-card-warn' : '' }}">
                <div class="ud-module-icon ud-icon-amber">⚖️</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Fairness Engine</div>
                    <div class="ud-module-stat">{{ $fairnessFlags }} flag(s) pending</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('increment.reviews') }}"
                class="ud-module-card {{ $pendingIncrements > 0 ? 'ud-card-warn' : '' }}">
                <div class="ud-module-icon ud-icon-green">💰</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Increments</div>
                    <div class="ud-module-stat">{{ $pendingIncrements }} pending approval</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('feedback.admin') }}"
                class="ud-module-card {{ $pendingFeedback > 0 ? 'ud-card-warn' : '' }}">
                <div class="ud-module-icon ud-icon-purple">📋</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Feedback Overview</div>
                    <div class="ud-module-stat">{{ $pendingFeedback }} pending submission</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
        </div>
    </div>

    {{-- AI & INSIGHTS --}}
    <div class="ud-module-group">
        <div class="ud-module-group-label">AI & Insights</div>
        <div class="ud-modules-grid">
            <a href="{{ route('ai.dashboard') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-dark">🤖</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">AI Intel</div>
                    <div class="ud-module-stat">Autonomous agent insights</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('feedback.bias-reports') }}"
                class="ud-module-card {{ $biasReports > 0 ? 'ud-card-alert' : '' }}">
                <div class="ud-module-icon ud-icon-red">⚠️</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Bias Reports</div>
                    <div class="ud-module-stat">{{ $biasReports }} unreviewed</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('reports.ceo') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-green">📈</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">CEO Dashboard</div>
                    <div class="ud-module-stat">Daily digest & org health</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
            <a href="{{ route('worklog.index') }}" class="ud-module-card">
                <div class="ud-module-icon ud-icon-blue">📝</div>
                <div class="ud-module-info">
                    <div class="ud-module-title">Work Log</div>
                    <div class="ud-module-stat">Log and track daily work</div>
                </div>
                <div class="ud-module-arrow">→</div>
            </a>
        </div>
    </div>

</div>

{{-- RECENT NOTIFICATIONS --}}
@if($recentNotifications->count() > 0)
<div class="ud-notifications-card">
    <div class="ud-card-header">
        <h3 class="ud-card-title">🔔 Recent Notifications</h3>
        <a href="{{ route('notifications.index') }}" class="ud-view-all-link">View all →</a>
    </div>
    <div class="ud-notif-list">
        @foreach($recentNotifications as $notif)
        <div class="ud-notif-row">
            <div class="ud-notif-dot"></div>
            <div class="ud-notif-content">
                <div class="ud-notif-title">{{ $notif->title }}</div>
                <div class="ud-notif-time">{{ $notif->created_at->diffForHumans() }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($latestAnnouncements) && $latestAnnouncements->count() > 0)
<div class="ud-notifications-card">
    <div class="ud-card-header">
        <h3 class="ud-card-title">📢 Latest Announcements</h3>
        <a href="{{ route('announcements.index') }}" class="ud-view-all-link">View all →</a>
    </div>
    <div class="ud-notif-list">
        @foreach($latestAnnouncements as $ann)
        <div class="ud-notif-row">
            <div class="ud-notif-dot" style="{{ $ann->priority === 'urgent' ? 'background:#ef4444' : '' }}"></div>
            <div class="ud-notif-content">
                <div class="ud-notif-title">
                    @if($ann->priority === 'urgent')🚨 @endif{{ $ann->title }}
                </div>
                <div class="ud-notif-time">
                    {{ $ann->author?->name }} · {{ $ann->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@php
$pendingLeaveApps = \App\Models\LeaveApplication::forOrg(auth()->user()->organization_id)
    ->where('status','pending')
    ->with('user','leaveType')
    ->orderBy('from_date')
    ->take(5)
    ->get();
@endphp
@if($pendingLeaveApps->count() > 0)
<div class="ud-notifications-card">
    <div class="ud-card-header">
        <h3 class="ud-card-title">🏖️ Pending Leave Requests</h3>
        <a href="{{ route('leaves.index') }}" class="ud-view-all-link">Review all →</a>
    </div>
    <div class="ud-notif-list">
        @foreach($pendingLeaveApps as $app)
        <div class="ud-notif-row">
            <div class="ud-notif-dot" style="background:#f59e0b"></div>
            <div class="ud-notif-content">
                <div class="ud-notif-title">{{ $app->user?->name ?? 'Unknown' }} — {{ $app->leaveType?->name ?? '—' }}</div>
                <div class="ud-notif-time">
                    {{ $app->from_date->format('M d') }}@if($app->from_date != $app->to_date) → {{ $app->to_date->format('M d') }}@endif
                    · {{ $app->is_half_day ? '½ day' : number_format($app->days, 1).' day'.($app->days != 1 ? 's' : '') }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- DIVIDER --}}
<div style="height:1px;background:#f4f4f5;margin:28px 0;"></div>

{{-- GITHUB STAT CARDS --}}
@if($modules['github_sync'] ?? true)
<div class="dash-grid-4">
    <div class="fv-stat">
        <div class="fv-stat-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div class="fv-stat-number">{{ number_format($activitySummary['total_commits']) }}</div>
        <div class="fv-stat-label">Commits (30 days)</div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="fv-stat-number">{{ number_format($activitySummary['total_prs']) }}</div>
        <div class="fv-stat-label">Pull Requests</div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        </div>
        <div class="fv-stat-number">{{ $activitySummary['avg_score'] }}</div>
        <div class="fv-stat-label">Avg Quality Score</div>
    </div>
    <div class="fv-stat text-center flex-col btn-center">
        <div class="fv-stat-label">Last Synced</div>
        <div class="fv-kpi-sync-text mt-sm mb-md">
            @if($activitySummary['last_synced'])
                {{ \Carbon\Carbon::parse($activitySummary['last_synced'])->diffForHumans() }}
            @else
                <span class="fv-kpi-never">Never synced</span>
            @endif
        </div>
        @can('sync_github')
        <form method="POST" action="{{ route('dashboard.sync-github') }}" id="sync-form">
            @csrf
            <button type="submit" id="sync-btn" class="fv-btn fv-btn-primary w-full btn-sm-center">
                <svg id="sync-icon" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span id="sync-text">Sync GitHub</span>
            </button>
        </form>
        @endcan
    </div>
</div>
@endif

{{-- DIRECT REPORTS --}}
@if(isset($myDirectReports) && $myDirectReports > 0)
<div class="fv-card p-lg mb-md mt-lg">
    <div class="dash-hierarchy-row">
        <div class="dash-hierarchy-info">
            <div class="heading-sm">My Direct Reports</div>
            <div class="text-muted-xs mt-xs">{{ $myReportsLogged ?? 0 }} of {{ $myDirectReports }} logged work today</div>
        </div>
        <a href="{{ route('team.index') }}" class="btn-secondary">View Team</a>
    </div>
</div>
@endif

{{-- GITHUB CHART --}}
@if($modules['github_sync'] ?? true)
<div class="fv-card p-lg mb-md mt-lg">
    <div class="flex-between mb-md flex-wrap gap-md">
        <div>
            <div class="heading-sm">GitHub Activity</div>
            <div class="text-muted-xs mt-xs">Commits and Pull Requests — Last 30 Days</div>
        </div>
        <div class="chart-legend-wrap">
            <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-black"></span> Commits</span>
            <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-gray"></span> PRs</span>
        </div>
    </div>
    <canvas id="activityChart" height="100"></canvas>
</div>
@endif

{{-- ACTIVITY TABLE + QUICK ACTIONS --}}
<div class="dash-two-col mt-lg">
    @if($modules['github_sync'] ?? true)
    <div class="fv-card">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">Recent Activity</div>
                <div class="fv-section-sub">Last 20 events across your organization</div>
            </div>
            <span class="fv-badge fv-badge-gray">{{ $recentActivities->count() }} events</span>
        </div>
        @if($recentActivities->isEmpty())
        <div class="fv-empty">
            <div class="fv-empty-title">No activity yet</div>
            <div class="fv-empty-desc">Click Sync GitHub to load contribution data.</div>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="fv-table">
                <thead><tr><th>Date</th><th>Project</th><th>Type</th><th>Score</th></tr></thead>
                <tbody>
                    @foreach($recentActivities as $activity)
                    @php
                        $score  = $activity->complexity_score ?? $activity->quality_score ?? $activity->impact_score;
                        $barPct = $score !== null ? min(100, ((float)$score / 10) * 100) : 0;
                    @endphp
                    <tr>
                        <td class="text-muted-xs text-nowrap">{{ $activity->occurred_at->format('M j') }}</td>
                        <td class="heading-sm">{{ $activity->project?->name ?? '—' }}</td>
                        <td><span class="fv-badge fv-badge-gray">{{ ucfirst(str_replace('_', ' ', $activity->event_type)) }}</span></td>
                        <td>
                            @if($score !== null)
                            <div class="score-bar-wrap">
                                <progress class="score-progress" value="{{ $barPct }}" max="100"></progress>
                                <span class="score-text">{{ number_format((float)$score, 2) }}</span>
                            </div>
                            @else
                            <span class="text-muted-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    <div class="dash-right-col">
        <div class="fv-card p-lg">
            <div class="heading-sm mb-md">Quick Actions</div>
            <div class="dash-quick-actions">
                @if($modules['github_sync'] ?? true)
                @can('sync_github')
                <form method="POST" action="{{ route('dashboard.sync-github') }}">
                    @csrf
                    <button type="submit" class="fv-btn fv-btn-primary w-full btn-sm-center">Sync GitHub</button>
                </form>
                @endcan
                @endif
                @can('view_team')
                <a href="{{ route('team.index') }}" class="fv-btn fv-btn-secondary btn-sm-center">View Team</a>
                @endcan
                @if($modules['fairness_engine'] ?? true)
                @can('run_fairness_analysis')
                <form method="POST" action="{{ route('fairness.run') }}">
                    @csrf
                    <button type="submit" class="fv-btn fv-btn-secondary w-full btn-sm-center">Run Fairness Analysis</button>
                </form>
                @endcan
                @endif
                @if($modules['ai_intelligence'] ?? true)
                @can('view_ai')
                <a href="{{ route('ai.dashboard') }}" class="fv-btn fv-btn-secondary btn-sm-center">AI Intelligence →</a>
                @endcan
                @endif
            </div>
        </div>
        @if(isset($aiSummary) && $aiSummary)
        <div class="fv-card dash-ai-card">
            <div class="dash-ai-header">
                <span class="dash-ai-title">AI Summary</span>
                <span class="dash-ai-time">{{ now()->format('g:i A') }}</span>
            </div>
            <div class="dash-ai-body">
                <p class="dash-ai-text">{{ $aiSummary }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/unified-dashboard.js') }}" defer></script>
<script>
document.getElementById('sync-form')?.addEventListener('submit', function() {
    const btn  = document.getElementById('sync-btn');
    const txt  = document.getElementById('sync-text');
    const icon = document.getElementById('sync-icon');
    if (btn)  { btn.disabled = true; btn.style.opacity = '0.75'; }
    if (icon) icon.style.animation = 'spin 0.7s linear infinite';
    if (txt)  txt.textContent = 'Syncing…';
});
const ctx = document.getElementById('activityChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels->values()) !!},
            datasets: [
                { label: 'Commits',       data: {!! json_encode($commitData->values()) !!}, backgroundColor: '#18181b', borderRadius: 3, borderSkipped: false, barThickness: 8 },
                { label: 'Pull Requests', data: {!! json_encode($prData->values()) !!},     backgroundColor: '#71717a', borderRadius: 3, borderSkipped: false, barThickness: 8 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, color: '#a1a1aa' } },
                y: { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { stepSize: 1, font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, color: '#a1a1aa' } }
            }
        }
    });
}
</script>
@endpush
