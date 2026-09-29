@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <meta name="sa-chart-data"     content="{{ json_encode(['free' => $planData['free'] ?? 0, 'pro' => $planData['pro'] ?? 0, 'enterprise' => $planData['enterprise'] ?? 0]) }}">
    <meta name="sa-growth-labels"  content="{{ json_encode($growthLabels->values()) }}">
    <meta name="sa-growth-counts"  content="{{ json_encode($growthCounts->values()) }}">
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Stats row --}}
    <div class="sa-stats-grid-5">
        @php $statCards = [
            ['Total Organizations', $stats['total_orgs'],     $stats['pending_orgs'] > 0 ? $stats['pending_orgs'].' pending' : null],
            ['Active Orgs',         $stats['active_orgs'],    null],
            ['Total Users',         $stats['total_users'],    null],
            ['Total Activities',    $stats['total_activities'], null],
        ]; @endphp

        @foreach($statCards as [$label, $val, $badge])
        <div class="fv-stat">
            <div class="fv-stat-label">{{ $label }}</div>
            <div class="sa-stat-badge-row">
                <div class="fv-stat-number">{{ number_format($val) }}</div>
                @if($badge)
                    <span class="fv-badge fv-badge-gray">{{ $badge }}</span>
                @endif
            </div>
        </div>
        @endforeach

        {{-- System Health card --}}
        @php
        $sysHealth  = \Illuminate\Support\Facades\Cache::get('system_health', []);
        $sysOverall = 'unknown';
        foreach ($sysHealth as $chk) {
            if (($chk['status'] ?? '') === 'critical') { $sysOverall = 'critical'; break; }
            if (($chk['status'] ?? '') === 'warning')  { $sysOverall = 'warning'; }
            if ($sysOverall === 'unknown') $sysOverall = 'healthy';
        }
        $sysLabel = match($sysOverall) {
            'healthy'  => 'Operational',
            'warning'  => 'Warning',
            'critical' => 'Critical',
            default    => 'Unknown',
        };
        @endphp
        <a href="{{ route('agent.health') }}" class="sa-health-stat {{ $sysOverall }}" title="View System Health">
            <div class="fv-stat-label sa-health-stat-label">
                <span class="sa-health-dot {{ $sysOverall }}"></span>
                System Health
            </div>
            <div class="sa-health-status {{ $sysOverall }}">{{ $sysLabel }}</div>
            <div class="sa-health-meta">{{ count($sysHealth) }} checks · Click to view</div>
        </a>
    </div>

    {{-- Charts --}}
    <div class="sa-charts-row">

        {{-- Plan Distribution --}}
        <div class="fv-card">
            <div class="fv-section-title">Plan Distribution</div>
            <div class="sa-chart-sub-mb">Organizations by subscription tier</div>
            <div class="sa-chart-inner">
                <div class="sa-chart-doughnut-wrap">
                    <canvas id="planChart" width="160" height="160"></canvas>
                    <div class="sa-chart-center">
                        <div class="sa-chart-center-num">{{ $stats['total_orgs'] }}</div>
                        <div class="sa-chart-center-label">total</div>
                    </div>
                </div>
                <div class="sa-chart-legend">
                    @foreach([['Free','free',$planData['free']??0],['Pro','pro',$planData['pro']??0],['Enterprise','enterprise',$planData['enterprise']??0]] as [$label,$key,$count])
                    <div class="sa-chart-legend-item">
                        <div class="sa-chart-legend-left">
                            <div class="sa-chart-legend-dot {{ $key }}"></div>
                            <span class="sa-chart-legend-name">{{ $label }}</span>
                        </div>
                        <span class="sa-chart-legend-count">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Org Growth --}}
        <div class="fv-card">
            <div class="fv-section-title">Organization Growth</div>
            <div class="sa-chart-sub-mb">New organizations — last 6 months</div>
            <canvas id="growthChart" height="130"></canvas>
        </div>
    </div>

    {{-- Pending Organizations --}}
    @if($pendingOrgs->count() > 0)
    <div>
        <div class="sa-pending-banner mb-md">
            <svg class="sa-icon-sm sa-icon-warn" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="sa-pending-banner-text">{{ $pendingOrgs->count() }} Organization{{ $pendingOrgs->count() > 1 ? 's' : '' }} Awaiting Approval</span>
        </div>
        <div class="fv-card">
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead><tr>
                        <th>Organization</th><th>Owner</th><th>Owner Email</th>
                        <th>Members</th><th>Registered</th><th class="sa-th-right">Actions</th>
                    </tr></thead>
                    <tbody>
                        @foreach($pendingOrgs as $org)
                        <tr>
                            <td>
                                <div class="sa-td-primary">{{ $org['name'] }}</div>
                                <div class="sa-td-sub sa-td-mono">{{ $org['slug'] }}</div>
                            </td>
                            <td class="sa-td-primary">{{ $org['owner_name'] }}</td>
                            <td class="sa-td-muted">{{ $org['owner_email'] }}</td>
                            <td class="sa-td-primary">{{ $org['members'] }}</td>
                            <td class="sa-td-muted">{{ $org['created_at']->format('M j, Y') }}</td>
                            <td class="sa-td-actions">
                                <div class="sa-td-actions-inner">
                                    <form method="POST" action="{{ route('superadmin.organizations.approve', $org['id']) }}">
                                        @csrf
                                        <button type="submit" class="fv-btn fv-btn-primary"
                                                data-confirm="Approve {{ addslashes($org['name']) }}?">Approve</button>
                                    </form>
                                    <a href="{{ route('superadmin.organizations.show', $org['id']) }}" class="fv-btn fv-btn-secondary">View</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Recent Organizations --}}
    <div class="fv-card">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">Recent Organizations</div>
                <div class="fv-section-sub">Last {{ $recentOrgs->count() }} organizations on the platform.</div>
            </div>
            <a href="{{ route('superadmin.organizations') }}" class="fv-btn fv-btn-secondary">View All</a>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr>
                    <th>Name</th><th>Plan</th><th>Status</th>
                    <th>Members</th><th>Owner</th><th>Created</th>
                    <th class="sa-th-right">Actions</th>
                </tr></thead>
                <tbody>
                    @foreach($recentOrgs as $org)
                    <tr>
                        <td>
                            <div class="sa-td-primary">{{ $org['name'] }}</div>
                            <div class="sa-td-sub sa-td-mono">{{ $org['slug'] }}</div>
                        </td>
                        <td><span class="sa-plan-badge sa-plan-{{ $org['plan'] }}">{{ ucfirst($org['plan']) }}</span></td>
                        <td><span class="sa-status-badge sa-status-{{ $org['status'] }}">{{ ucfirst($org['status']) }}</span></td>
                        <td class="sa-td-primary">{{ $org['members'] }}</td>
                        <td class="sa-td-muted">{{ $org['owner_name'] }}</td>
                        <td class="sa-td-muted">{{ $org['created_at']->format('M j, Y') }}</td>
                        <td class="sa-td-actions">
                            <div class="sa-td-actions-inner">
                                <a href="{{ route('superadmin.organizations.show', $org['id']) }}" class="fv-btn fv-btn-secondary">View</a>
                                <form method="POST" action="{{ route('superadmin.organizations.plan', $org['id']) }}">
                                    @csrf
                                    <select name="plan" class="sa-plan-select" data-autosubmit>
                                        @foreach(['free','pro','enterprise'] as $p)
                                        <option value="{{ $p }}" {{ $org['plan'] === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                @if($org['status'] !== 'suspended')
                                <div x-data="{ reason: '' }">
                                    <form method="POST" action="{{ route('superadmin.organizations.suspend', $org['id']) }}" x-ref="suspendForm">
                                        @csrf
                                        <input type="hidden" name="reason" x-model="reason">
                                        <button type="button" class="fv-btn fv-btn-danger"
                                                @click="reason=prompt('Reason for suspension?');if(reason)$refs.suspendForm.submit()">Suspend</button>
                                    </form>
                                </div>
                                @else
                                <form method="POST" action="{{ route('superadmin.organizations.reactivate', $org['id']) }}">
                                    @csrf
                                    <button type="submit" class="fv-btn fv-btn-primary">Reactivate</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>document.addEventListener('DOMContentLoaded', initSACharts);</script>
@endpush
