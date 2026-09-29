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
        <h1 class="heading-xl mb-xs">{{ $greeting }}, {{ auth()->user()->name }} 👋</h1>
        <p class="text-muted-sm">{{ now()->format('l, F j, Y') }} · Your personal contribution summary</p>
    </div>
    <div class="dash-live-pill-green">
        <span class="live-dot"></span>
        <span class="dash-live-text-green">MY DASHBOARD</span>
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

{{-- Log Work & Onboarding CTA Banner --}}
<div class="fv-card p-lg mb-md bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white rounded-xl shadow-lg border border-blue-700/30">
    <div class="flex-between flex-wrap gap-md mb-md">
        <div>
            <div class="dash-live-pill-green inline-block mb-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                <span class="live-dot bg-emerald-400"></span>
                <span class="dash-live-text-green text-emerald-300 font-bold">GET STARTED IN 60 SECONDS</span>
            </div>
            <div class="heading-md text-white font-bold mb-xs">Welcome to OutraqHQ! Here's how your daily work gets rewarded:</div>
            <div class="text-white/80 text-sm">Follow these 3 quick steps to track your output, calculate fair increments, and protect your contributions.</div>
        </div>
        <a href="{{ route('worklog.create') }}" class="fv-btn bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-lg py-sm rounded-lg transition-all shadow-md">
            + Log Today's Work (30s)
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-md pt-sm border-t border-white/10">
        <a href="{{ route('settings.index') }}" class="p-sm rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all flex items-center gap-sm">
            <span class="w-7 h-7 rounded-full bg-blue-500/20 text-blue-300 flex items-center justify-center font-bold text-xs">1</span>
            <div>
                <div class="text-xs font-bold text-white">Connect GitHub Account</div>
                <div class="text-2xs text-white/70">Sync commits & pull requests</div>
            </div>
        </a>
        <a href="{{ route('worklog.create') }}" class="p-sm rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all flex items-center gap-sm">
            <span class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center font-bold text-xs">2</span>
            <div>
                <div class="text-xs font-bold text-white">Log Today's Work Log</div>
                <div class="text-2xs text-white/70">Record meetings, code, & tasks</div>
            </div>
        </a>
        <a href="{{ route('increment.my') }}" class="p-sm rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all flex items-center gap-sm">
            <span class="w-7 h-7 rounded-full bg-amber-500/20 text-amber-300 flex items-center justify-center font-bold text-xs">3</span>
            <div>
                <div class="text-xs font-bold text-white">View Performance Score</div>
                <div class="text-2xs text-white/70">See monthly increment breakdown</div>
            </div>
        </a>
    </div>
</div>

{{-- My Stats (3 cards) --}}
<div class="dash-grid-3">

    <div class="fv-kpi-card fv-kpi-card-black">
        <div class="fv-kpi-icon fv-kpi-icon-gray">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div class="fv-kpi-label">MY COMMITS (30 DAYS)</div>
        <div class="fv-kpi-value">{{ number_format($commits) }}</div>
        <div class="fv-kpi-sub">Your contributions</div>
    </div>

    <div class="fv-kpi-card fv-kpi-card-green">
        <div class="fv-kpi-icon fv-kpi-icon-green">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="fv-kpi-label">MY PULL REQUESTS</div>
        <div class="fv-kpi-value">{{ number_format($prs) }}</div>
        <div class="fv-kpi-sub">Last 30 days</div>
    </div>

    <div class="fv-kpi-card fv-kpi-card-amber">
        <div class="fv-kpi-icon fv-kpi-icon-amber">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        </div>
        <div class="fv-kpi-label">MY SCORE</div>
        <div class="fv-kpi-value">{{ $avgScore }}</div>
        <div class="fv-kpi-sub">Avg complexity score</div>
    </div>
</div>

{{-- My Activity Chart --}}
<div class="dash-chart-wrap">
    <div class="fv-card p-lg">
        <div class="flex-between mb-lg flex-wrap gap-md">
            <div>
                <div class="heading-md">My Activity</div>
                <div class="text-muted-xs mt-xs">Your personal activity — Last 30 Days</div>
            </div>
            <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-black"></span> Events</span>
        </div>
        <canvas id="myActivityChart" height="100"></canvas>
    </div>
</div>

{{-- Two-column: Recent Activity + Blockers --}}
<div class="dash-two-col-lg">

    <div class="fv-card">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">My Recent Activity</div>
                <div class="fv-section-sub">Your last {{ $recentActivities->count() }} events</div>
            </div>
            <span class="fv-badge fv-badge-gray">{{ $recentActivities->count() }}</span>
        </div>
        @if($recentActivities->isEmpty())
        <div class="fv-empty">
            <div class="fv-empty-title">No activity yet</div>
            <div class="fv-empty-desc">Your commits and PRs will appear here after syncing.</div>
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
            <div class="flex-between mb-md">
                <div class="heading-sm">My Blockers</div>
                @if($myBlockers->isNotEmpty())
                <span class="fv-badge fv-badge-error">{{ $myBlockers->count() }} open</span>
                @endif
            </div>
            @forelse($myBlockers as $blocker)
            @php $priority = $blocker->priority ?? 'medium'; @endphp
            <div class="blocker-item blocker-item-{{ $priority }}">
                <div class="blocker-item-title">{{ $blocker->title }}</div>
                <div class="blocker-item-meta">
                    {{ ucfirst($priority) }} · {{ $blocker->project?->name ?? 'General' }}
                </div>
            </div>
            @empty
            <div class="fv-empty fv-empty-sm">
                <div class="fv-empty-title">✓ No open blockers</div>
            </div>
            @endforelse
            @can('create_blockers')
            <a href="{{ route('dependency.index') }}" class="fv-btn fv-btn-secondary w-full mt-sm btn-sm-center">
                Report a Blocker
            </a>
            @endcan
        </div>

        <div class="fv-card p-lg">
            <div class="heading-sm mb-md">Active Projects</div>
            @forelse($myProjects as $project)
            <a href="{{ route('projects.show', $project->id) }}" class="project-link">
                <div>
                    <div class="project-link-name">{{ $project->name }}</div>
                    @if($project->github_repo)
                    <div class="project-link-repo">{{ $project->github_repo }}</div>
                    @endif
                </div>
                <svg class="project-link-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @empty
            <div class="fv-empty fv-empty-sm">
                <div class="fv-empty-desc">No active projects yet.</div>
            </div>
            @endforelse
        </div>
    </div>
</div>

</div>

@push('scripts')
<script>
const myCtx = document.getElementById('myActivityChart');
if (myCtx) {
    new Chart(myCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels->values()) !!},
            datasets: [{
                label: 'Activities',
                data: {!! json_encode($activityCounts->values()) !!},
                borderColor: '#18181b',
                backgroundColor: 'rgba(24,24,27,0.08)',
                borderWidth: 2, fill: true, tension: 0.4,
                pointBackgroundColor: '#18181b', pointRadius: 3, pointHoverRadius: 5,
            }]
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
