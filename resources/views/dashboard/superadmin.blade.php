@extends('layouts.app')
@section('title', 'Platform Control Panel')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

{{-- HEADER --}}
<div class="sa-header">
    <div>
        <h1 class="sa-title">Platform Control Panel</h1>
        <p class="sa-subtitle">{{ now()->format('l, F j, Y') }} · OutraqHQ Super Admin</p>
    </div>
    <div class="sa-live-badge">
        <span class="sa-live-dot"></span>
        All Systems Operational
    </div>
</div>

{{-- ROW 1: 4 METRIC CARDS --}}
<div class="sa-metrics">
    <div class="sa-metric-card">
        <div class="sa-metric-top">
            <div class="sa-metric-icon-wrap">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <span class="sa-metric-trend sa-trend-up">+{{ $recentSignups }} this week</span>
        </div>
        <div class="sa-metric-value">{{ $totalOrgs }}</div>
        <div class="sa-metric-label">Total Organizations</div>
        <div class="sa-metric-sub">{{ $activeOrgs }} active · {{ $totalOrgs - $activeOrgs }} inactive</div>
    </div>

    <div class="sa-metric-card">
        <div class="sa-metric-top">
            <div class="sa-metric-icon-wrap">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="sa-metric-trend sa-trend-up">{{ $activeUsers }} active</span>
        </div>
        <div class="sa-metric-value">{{ $totalUsers }}</div>
        <div class="sa-metric-label">Total Users</div>
        <div class="sa-metric-sub">Across all organizations</div>
    </div>

    <div class="sa-metric-card">
        <div class="sa-metric-top">
            <div class="sa-metric-icon-wrap">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="sa-metric-trend sa-trend-up">Today</span>
        </div>
        <div class="sa-metric-value">{{ $activeToday }}</div>
        <div class="sa-metric-label">Active Today</div>
        <div class="sa-metric-sub">Users who logged work today</div>
    </div>

    <div class="sa-metric-card">
        <div class="sa-metric-top">
            <div class="sa-metric-icon-wrap">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <span class="sa-metric-trend">Templates</span>
        </div>
        <div class="sa-metric-value">{{ $totalDesignations }}</div>
        <div class="sa-metric-label">Designations</div>
        <div class="sa-metric-sub">Platform-wide templates</div>
    </div>
</div>

{{-- ROW 2: PLAN BREAKDOWN + ORG GROWTH CHART --}}
<div class="sa-row-2">

    <div class="sa-card">
        <div class="sa-card-head">
            <div class="sa-card-title">Plan Distribution</div>
            <div class="sa-card-sub">Organizations by plan</div>
        </div>
        <div class="sa-plan-breakdown">
            <div class="sa-plan-row">
                <div class="sa-plan-info">
                    <span class="sa-plan-dot sa-dot-free"></span>
                    <span class="sa-plan-name">Free</span>
                </div>
                <div class="sa-plan-bar-wrap">
                    <div class="sa-plan-bar">
                        <div class="sa-plan-fill sa-fill-free"
                            style="width:{{ $totalOrgs > 0 ? round(($freePlanOrgs/$totalOrgs)*100) : 0 }}%"></div>
                    </div>
                </div>
                <span class="sa-plan-count">{{ $freePlanOrgs }}</span>
            </div>
            <div class="sa-plan-row">
                <div class="sa-plan-info">
                    <span class="sa-plan-dot sa-dot-growth"></span>
                    <span class="sa-plan-name">Growth</span>
                </div>
                <div class="sa-plan-bar-wrap">
                    <div class="sa-plan-bar">
                        <div class="sa-plan-fill sa-fill-growth"
                            style="width:{{ $totalOrgs > 0 ? round(($growthPlanOrgs/$totalOrgs)*100) : 0 }}%"></div>
                    </div>
                </div>
                <span class="sa-plan-count">{{ $growthPlanOrgs }}</span>
            </div>
            <div class="sa-plan-row">
                <div class="sa-plan-info">
                    <span class="sa-plan-dot sa-dot-enterprise"></span>
                    <span class="sa-plan-name">Enterprise</span>
                </div>
                <div class="sa-plan-bar-wrap">
                    <div class="sa-plan-bar">
                        <div class="sa-plan-fill sa-fill-enterprise"
                            style="width:{{ $totalOrgs > 0 ? round(($enterprisePlanOrgs/$totalOrgs)*100) : 0 }}%"></div>
                    </div>
                </div>
                <span class="sa-plan-count">{{ $enterprisePlanOrgs }}</span>
            </div>
        </div>

        <div class="sa-mini-stats">
            <div class="sa-mini-stat">
                <div class="sa-mini-val">{{ $activeOrgs }}</div>
                <div class="sa-mini-lbl">Active</div>
            </div>
            <div class="sa-mini-stat">
                <div class="sa-mini-val">{{ $suspendedOrgs }}</div>
                <div class="sa-mini-lbl">Suspended</div>
            </div>
            <div class="sa-mini-stat">
                <div class="sa-mini-val">{{ $recentSignups }}</div>
                <div class="sa-mini-lbl">This Week</div>
            </div>
        </div>
    </div>

    <div class="sa-card sa-card-wide">
        <div class="sa-card-head">
            <div class="sa-card-title">Organization Growth</div>
            <div class="sa-card-sub">New organizations — Last 30 days</div>
        </div>
        <canvas id="orgGrowthChart" height="120"></canvas>
    </div>

</div>

{{-- ROW 3: USER GROWTH + QUICK ACTIONS --}}
<div class="sa-row-3">

    <div class="sa-card sa-card-wide">
        <div class="sa-card-head">
            <div class="sa-card-title">User Registrations</div>
            <div class="sa-card-sub">New users per week — Last 8 weeks</div>
        </div>
        <canvas id="userGrowthChart" height="120"></canvas>
    </div>

    <div class="sa-card">
        <div class="sa-card-head">
            <div class="sa-card-title">Quick Actions</div>
        </div>
        <div class="sa-quick-actions">
            <a href="{{ route('superadmin.organizations') }}" class="sa-qa-item sa-qa-primary">
                <span class="sa-qa-icon">🏢</span>
                <div>
                    <div class="sa-qa-title">Manage Organizations</div>
                    <div class="sa-qa-sub">View, edit, suspend, delete</div>
                </div>
            </a>
            <a href="{{ route('superadmin.users') }}" class="sa-qa-item">
                <span class="sa-qa-icon">👥</span>
                <div>
                    <div class="sa-qa-title">Manage All Users</div>
                    <div class="sa-qa-sub">Across all organizations</div>
                </div>
            </a>
            <a href="{{ route('superadmin.designations') }}" class="sa-qa-item">
                <span class="sa-qa-icon">🏷️</span>
                <div>
                    <div class="sa-qa-title">Designations</div>
                    <div class="sa-qa-sub">Platform templates</div>
                </div>
            </a>
            <a href="{{ route('agent.health') }}" class="sa-qa-item">
                <span class="sa-qa-icon">💚</span>
                <div>
                    <div class="sa-qa-title">Platform Health</div>
                    <div class="sa-qa-sub">System status & logs</div>
                </div>
            </a>
            <a href="{{ route('agent.run') }}" class="sa-qa-item"
                onclick="event.preventDefault(); document.getElementById('run-agent').submit()">
                <span class="sa-qa-icon">🤖</span>
                <div>
                    <div class="sa-qa-title">Run AI Agent</div>
                    <div class="sa-qa-sub">Manual trigger</div>
                </div>
            </a>
            <form id="run-agent" method="POST" action="{{ route('agent.run') }}" style="display:none">@csrf</form>
        </div>
    </div>

</div>

{{-- ROW 4: RECENT NOTIFICATIONS --}}
@if($recentNotifications->count() > 0)
<div class="sa-card sa-card-full">
    <div class="sa-card-head">
        <div class="sa-card-title">🔔 Recent Notifications</div>
        <a href="{{ route('notifications.index') }}" class="sa-see-all">View all →</a>
    </div>
    <div class="sa-notif-grid">
        @foreach($recentNotifications as $n)
        <div class="sa-notif-item">
            <div class="sa-notif-dot"></div>
            <div>
                <div class="sa-notif-title">{{ $n->title }}</div>
                <div class="sa-notif-time">{{ $n->created_at->diffForHumans() }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const orgCtx = document.getElementById('orgGrowthChart');
if (orgCtx) {
    new Chart(orgCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($orgGrowthLabels->values()) !!},
            datasets: [{
                label: 'New Orgs',
                data: {!! json_encode($orgGrowthData->values()) !!},
                borderColor: '#18181b',
                backgroundColor: 'rgba(24,24,27,0.06)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#18181b',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11, family: "'Plus Jakarta Sans',sans-serif" }, color: '#a1a1aa' } },
                y: { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { stepSize: 1, font: { size: 11, family: "'Plus Jakarta Sans',sans-serif" }, color: '#a1a1aa' } }
            }
        }
    });
}

const userCtx = document.getElementById('userGrowthChart');
if (userCtx) {
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($userGrowthLabels->values()) !!},
            datasets: [{
                label: 'New Users',
                data: {!! json_encode($userGrowthData->values()) !!},
                backgroundColor: '#18181b',
                borderRadius: 4,
                borderSkipped: false,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11, family: "'Plus Jakarta Sans',sans-serif" }, color: '#a1a1aa' } },
                y: { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { stepSize: 1, font: { size: 11, family: "'Plus Jakarta Sans',sans-serif" }, color: '#a1a1aa' } }
            }
        }
    });
}
</script>
@endpush
