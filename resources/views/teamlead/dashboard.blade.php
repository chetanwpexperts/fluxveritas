@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

@php
$hour = now()->hour;
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="page-container">

{{-- Header --}}
<div class="dash-page-header-0">
    <div>
        <span class="dash-role-badge">Team Lead</span>
        <h1 class="heading-xl mb-xs">{{ $greeting }}, {{ auth()->user()->name }} 👋</h1>
        <p class="text-muted-sm">{{ now()->format('l, F j, Y') }} · Team overview and contribution intelligence</p>
    </div>
    <div class="dash-live-pill-green">
        <span class="live-dot"></span>
        <span class="dash-live-text-green">LIVE TRACKING</span>
    </div>
</div>

{{-- Alerts --}}
<div class="dash-alerts">
    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
    <div class="fv-alert fv-alert-warning mb-md">{{ session('warning') }}</div>
    @endif
    @if(session('info'))
    <div class="fv-alert fv-alert-info mb-md">{{ session('info') }}</div>
    @endif
</div>

{{-- Stats (4 cards) --}}
<div class="dash-grid-4-tight">

    <div class="fv-kpi-card fv-kpi-card-black">
        <div class="fv-kpi-icon fv-kpi-icon-gray">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div class="fv-kpi-label">COMMITS (30 DAYS)</div>
        <div class="fv-kpi-value">{{ number_format($activitySummary['total_commits']) }}</div>
        <div class="fv-kpi-sub">Team total</div>
    </div>

    <div class="fv-kpi-card fv-kpi-card-green">
        <div class="fv-kpi-icon fv-kpi-icon-green">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="fv-kpi-label">PULL REQUESTS</div>
        <div class="fv-kpi-value">{{ number_format($activitySummary['total_prs']) }}</div>
        <div class="fv-kpi-sub">Last 30 days</div>
    </div>

    <div class="fv-kpi-card fv-kpi-card-amber">
        <div class="fv-kpi-icon fv-kpi-icon-amber">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        </div>
        <div class="fv-kpi-label">AVG QUALITY SCORE</div>
        <div class="fv-kpi-value">{{ $activitySummary['avg_score'] }}</div>
        <div class="fv-kpi-sub">Team average</div>
    </div>

    <div class="fv-kpi-card fv-kpi-card-gray fv-kpi-card-col">
        <div>
            <div class="fv-kpi-label mb-sm">LAST SYNCED</div>
            <div class="fv-kpi-sync-text">
                @if($activitySummary['last_synced'])
                    {{ \Carbon\Carbon::parse($activitySummary['last_synced'])->diffForHumans() }}
                @else
                    <span class="fv-kpi-never">Never synced</span>
                @endif
            </div>
        </div>
        @can('sync_github')
        <form method="POST" action="{{ route('dashboard.sync-github') }}">
            @csrf
            <button type="submit" class="fv-btn fv-btn-primary w-full btn-sm-center">Sync GitHub</button>
        </form>
        @endcan
    </div>
</div>

{{-- Hierarchy Stats --}}
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

{{-- Team Activity Chart --}}
<div class="dash-chart-wrap">
    <div class="fv-card p-lg">
        <div class="flex-between mb-lg flex-wrap gap-md">
            <div>
                <div class="heading-md">Team GitHub Activity</div>
                <div class="text-muted-xs mt-xs">Commits and Pull Requests — Last 30 Days</div>
            </div>
            <div class="chart-legend-wrap">
                <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-black"></span>Commits</span>
                <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-green"></span>PRs</span>
            </div>
        </div>
        <canvas id="teamActivityChart" height="100"></canvas>
    </div>
</div>

{{-- Two-column: Recent Activity + Quick Actions --}}
<div class="dash-two-col-lg">

    <div class="fv-card">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">Recent Team Activity</div>
                <div class="fv-section-sub">Last {{ $recentActivities->count() }} events across your organization</div>
            </div>
            <span class="fv-badge fv-badge-gray">{{ $recentActivities->count() }} events</span>
        </div>
        @if($recentActivities->isEmpty())
        <div class="fv-empty">
            <div class="fv-empty-title">No activity yet</div>
            <div class="fv-empty-desc">Sync GitHub to load team contribution data.</div>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="fv-table">
                <thead><tr><th>Date</th><th>Project</th><th>Type</th><th>Score</th></tr></thead>
                <tbody>
                    @foreach($recentActivities as $activity)
                    @php
                        $score = $activity->complexity_score ?? $activity->quality_score;
                        $barPct = $score ? min(100, ((float)$score / 10) * 100) : 0;
                        $type = $activity->event_type;
                        $badgeClass = str_contains($type, 'commit') ? 'fv-badge-gray' : (str_contains($type, 'pr') ? 'fv-badge-green' : 'fv-badge-gray');
                    @endphp
                    <tr>
                        <td class="text-muted-xs text-nowrap">{{ $activity->occurred_at->format('M j') }}</td>
                        <td class="heading-sm">{{ $activity->project?->name ?? '—' }}</td>
                        <td><span class="fv-badge {{ $badgeClass }}">{{ ucfirst(str_replace('_',' ',$type)) }}</span></td>
                        <td>
                            @if($score)
                            <div class="score-bar-wrap">
                                <progress class="score-progress" value="{{ $barPct }}" max="100"></progress>
                                <span class="score-text">{{ number_format((float)$score,2) }}</span>
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

    <div class="dash-right-col">
        <div class="fv-card p-lg">
            <div class="heading-sm mb-md">Team Lead Actions</div>
            <div class="dash-quick-actions">
                <a href="{{ route('ceo.index') }}" class="fv-btn fv-btn-primary btn-sm-center">Command Center</a>
                <a href="{{ route('team.index') }}" class="fv-btn fv-btn-secondary btn-sm-center">View Team</a>
                @can('view_fairness')
                <a href="{{ route('fairness.index') }}" class="fv-btn fv-btn-secondary btn-sm-center btn-fairness">Fairness Flags</a>
                @endcan
                @can('view_blockers')
                <a href="{{ route('dependency.index') }}" class="fv-btn fv-btn-secondary btn-sm-center btn-danger-outline">Active Blockers</a>
                @endcan
                @can('view_ai')
                <a href="{{ route('ai.dashboard') }}" class="fv-btn fv-btn-secondary btn-sm-center btn-ai">AI Intelligence →</a>
                @endcan
            </div>
        </div>
        <div class="fv-card p-lg surface-card">
            <div class="heading-sm mb-sm">Team Lead Access</div>
            <p class="text-muted-xs text-relaxed">You have visibility into team performance, fairness analysis, and blocker management. Use the Command Center for the full picture.</p>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
const tlCtx = document.getElementById('teamActivityChart');
if (tlCtx) {
    new Chart(tlCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels->values()) !!},
            datasets: [
                { label: 'Commits', data: {!! json_encode($commitData->values()) !!}, backgroundColor: 'rgba(24,24,27,0.8)', borderRadius: 4, borderSkipped: false },
                { label: 'PRs',     data: {!! json_encode($prData->values()) !!},     backgroundColor: 'rgba(5,150,105,0.8)',  borderRadius: 4, borderSkipped: false }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1, font: { size: 11 } } }
            }
        }
    });
}
</script>
@endpush
@endsection
