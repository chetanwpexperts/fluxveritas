@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

@php
$hour = now()->hour;
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">{{ $greeting }}, {{ auth()->user()->name }} 👋</h1>
        <p class="page-subtitle">{{ now()->format('l, F j, Y') }} &middot; Your contribution intelligence</p>
    </div>
    <div class="page-header-right">
        <div class="dash-live-pill">
            <span class="live-dot"></span>
            <span class="dash-live-text">LIVE TRACKING</span>
        </div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="fv-alert fv-alert-success mb-md">
    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('warning'))
<div class="fv-alert fv-alert-warning mb-md">{{ session('warning') }}</div>
@endif
@if(session('info'))
<div class="fv-alert fv-alert-gray mb-md">{{ session('info') }}</div>
@endif
@if($errors->has('github'))
<div class="fv-alert fv-alert-error mb-md">{{ $errors->first('github') }}</div>
@endif

{{-- Stat Cards --}}
<div class="dash-grid-4">

    @if($modules['github_sync'] ?? true)
    <div class="fv-stat">
        <div class="fv-stat-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div class="fv-stat-number">{{ number_format($activitySummary['total_commits']) }}</div>
        <div class="fv-stat-label">Commits (30 days)</div>
    </div>
    @endif

    @if($modules['github_sync'] ?? true)
    <div class="fv-stat">
        <div class="fv-stat-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="fv-stat-number">{{ number_format($activitySummary['total_prs']) }}</div>
        <div class="fv-stat-label">Pull Requests</div>
    </div>
    @endif

    <div class="fv-stat">
        <div class="fv-stat-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        </div>
        <div class="fv-stat-number">{{ $activitySummary['avg_score'] }}</div>
        <div class="fv-stat-label">Avg Quality Score</div>
    </div>

    @if($modules['github_sync'] ?? true)
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
    @endif
</div>

{{-- Hierarchy Stats --}}
@if(auth()->user()->hasAnyRole(['admin', 'owner', 'manager', 'team_lead']))
@if($myDirectReports > 0)
<div class="fv-card p-lg mb-md">
    <div class="dash-hierarchy-row">
        <div class="dash-hierarchy-info">
            <div class="heading-sm">My Direct Reports</div>
            <div class="text-muted-xs mt-xs">{{ $myReportsLogged }} of {{ $myDirectReports }} logged work today</div>
        </div>
        <a href="{{ route('team.index') }}" class="btn-secondary">View Team</a>
    </div>
</div>
@else
<div class="fv-card p-lg mb-md">
    <div class="dash-hierarchy-row">
        <div class="dash-hierarchy-info">
            <div class="heading-sm">Direct Reports</div>
            <div class="text-muted-xs mt-xs">No team members assigned to you yet</div>
        </div>
        <a href="{{ route('admin.users') }}" class="btn-secondary">Assign Team →</a>
    </div>
</div>
@endif
@endif

{{-- Activity Chart --}}
@if($modules['github_sync'] ?? true)
<div class="fv-card p-lg mb-md">
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

{{-- Two-column layout --}}
<div class="dash-two-col">

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
                        $score = $activity->complexity_score ?? $activity->quality_score ?? $activity->impact_score;
                        $barPct = $score !== null ? min(100, ((float)$score / 10) * 100) : 0;
                        $type = $activity->event_type;
                    @endphp
                    <tr>
                        <td class="text-muted-xs text-nowrap">{{ $activity->occurred_at->format('M j') }}</td>
                        <td class="heading-sm">{{ $activity->project?->name ?? '—' }}</td>
                        <td><span class="fv-badge fv-badge-gray">{{ ucfirst(str_replace('_', ' ', $type)) }}</span></td>
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

    {{-- RIGHT: Quick Actions + AI Summary --}}
    <div class="dash-right-col">

        <div class="fv-card p-lg">
            <div class="heading-sm mb-md">Quick Actions</div>
            <div class="dash-quick-actions">
                @if($modules['github_sync'] ?? true)
                @can('sync_github')
                <form method="POST" action="{{ route('dashboard.sync-github') }}">
                    @csrf
                    <button type="submit" class="fv-btn fv-btn-primary w-full btn-sm-center">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Sync GitHub
                    </button>
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
                <a href="{{ route('ai.dashboard') }}" class="fv-btn fv-btn-secondary btn-sm-center">AI Intelligence &rarr;</a>
                @endcan
                @endif
            </div>
        </div>

        @if(isset($aiSummary))
        <div class="fv-card dash-ai-card">
            <div class="dash-ai-header">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="dash-ai-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
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

@push('scripts')
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
                { label: 'Commits', data: {!! json_encode($commitData->values()) !!}, backgroundColor: '#18181b', borderRadius: 3, borderSkipped: false, barThickness: 8 },
                { label: 'Pull Requests', data: {!! json_encode($prData->values()) !!}, backgroundColor: '#71717a', borderRadius: 3, borderSkipped: false, barThickness: 8 }
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
@endsection
