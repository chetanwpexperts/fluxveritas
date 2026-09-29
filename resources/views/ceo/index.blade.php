@extends('layouts.app')
@section('title', 'Command Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <div class="ceo-live-label-wrap">
            <span class="live-dot"></span>
            <span class="ceo-live-label">Live Command Center</span>
        </div>
        <h1 class="page-title">Command Center</h1>
        <p class="page-subtitle">Complete truth about your team &middot; No filters &middot; No noise</p>
    </div>
    <div class="page-header-right">
        <div class="text-right">
            <div id="live-date" class="ceo-live-date"></div>
            <div id="live-time" class="ceo-live-time"></div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     SECTION 2 — Health Row (8 stat cards)
═══════════════════════════════════════════ --}}
<div class="dash-grid-4-tight">
    <div class="fv-stat">
        <div class="fv-stat-label">Total Members</div>
        <div class="fv-stat-number">{{ $overview['total_members'] }}</div>
        <div class="fv-stat-sub">team members</div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-label">Total Commits</div>
        <div class="fv-stat-number">{{ $overview['total_commits'] }}</div>
        <div class="fv-stat-sub">last 30 days</div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-label">Pull Requests</div>
        <div class="fv-stat-number">{{ $overview['total_prs'] }}</div>
        <div class="fv-stat-sub">last 30 days</div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-label">Active Projects</div>
        <div class="fv-stat-number">{{ $overview['active_projects'] }}</div>
        <div class="fv-stat-sub">in progress</div>
    </div>
</div>
<div class="dash-grid-4-tight">
    <div class="fv-stat">
        <div class="fv-stat-label">Open Blockers</div>
        <div class="fv-stat-number {{ $overview['total_blockers'] > 0 ? 'fv-stat-number-danger' : '' }}">{{ $overview['total_blockers'] }}</div>
        <div class="{{ $overview['total_blockers'] > 0 ? 'fv-stat-sub-danger' : 'fv-stat-sub' }}">
            {{ $overview['total_blockers'] > 0 ? 'needs action' : 'all clear' }}
        </div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-label">Pending Flags</div>
        <div class="fv-stat-number {{ $overview['total_flags'] > 0 ? 'fv-stat-number-warning' : '' }}">{{ $overview['total_flags'] }}</div>
        <div class="{{ $overview['total_flags'] > 0 ? 'fv-stat-sub-warning' : 'fv-stat-sub' }}">
            {{ $overview['total_flags'] > 0 ? 'review needed' : 'no issues' }}
        </div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-label">Top Performer</div>
        <div class="ceo-top-performer">{{ $overview['top_performer'] }}</div>
        <div class="fv-stat-sub">this month</div>
    </div>
    <div class="fv-stat">
        <div class="fv-stat-label flex-center-gap-xs">
            Needs Attention
            <span title="Members with active flags or blockers" class="ceo-needs-help-tooltip">?</span>
        </div>
        <div class="fv-stat-number {{ $overview['needs_attention'] > 0 ? 'fv-stat-number-danger' : '' }}">{{ $overview['needs_attention'] }}</div>
        <div class="{{ $overview['needs_attention'] > 0 ? 'fv-stat-sub-danger' : 'fv-stat-sub' }}">
            {{ $overview['needs_attention'] > 0 ? 'members flagged' : 'everyone good' }}
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     SECTION 3 — AI Executive Summary
═══════════════════════════════════════════ --}}
@if($modules['ai_intelligence'] ?? true)
<div class="fv-card ceo-section-ai">
    <div class="ceo-ai-header">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="ceo-ai-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        <span class="ceo-ai-title">AI Summary</span>
        <span class="ceo-ai-time">Updated {{ now()->format('g:i A') }}</span>
    </div>
    <div class="ceo-ai-body">
        <p class="ceo-ai-text">{{ $aiSummary }}</p>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════
     SECTION 3b — Department Overview
═══════════════════════════════════════════ --}}
@if($departments->isNotEmpty())
<div class="fv-card ceo-mb">
    <div class="fv-section-header">
        <div>
            <div class="fv-section-title">Department Overview</div>
            <div class="fv-section-sub">All departments — work modes, activity, and this week's output</div>
        </div>
        <a href="{{ route('departments.index') }}" class="ceo-section-link">Manage →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="ceo-dept-table">
            <thead>
                <tr>
                    <th class="ceo-dept-th">Department</th>
                    <th class="ceo-dept-th">Mode</th>
                    <th class="ceo-dept-th ceo-dept-th-center">Members</th>
                    <th class="ceo-dept-th ceo-dept-th-center">Today's Logs</th>
                    <th class="ceo-dept-th ceo-dept-th-center">Week Hours</th>
                    <th class="ceo-dept-th ceo-dept-th-center">GitHub (7d)</th>
                    <th class="ceo-dept-th"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $dept)
                @php
                    $modeLabel = match($dept['work_mode']) { 'github' => 'GitHub', 'hybrid' => 'Hybrid', default => 'Manual' };
                @endphp
                <tr class="ceo-dept-tr">
                    <td class="ceo-dept-td">
                        <div class="ceo-dept-name-cell">
                            <div class="dept-color-dot" style="--dept-color:{{ $dept['color'] }}"></div>
                            <div>
                                <div class="ceo-dept-name">{{ $dept['name'] }}</div>
                                <div class="ceo-dept-type">{{ $dept['type'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="ceo-dept-td">
                        <span class="dept-mode-badge dept-mode-{{ $dept['work_mode'] }}">{{ $modeLabel }}</span>
                    </td>
                    <td class="ceo-dept-td-center">
                        <span class="ceo-dept-num">{{ $dept['member_count'] }}</span>
                    </td>
                    <td class="ceo-dept-td-center">
                        <span class="{{ $dept['today_logs'] > 0 ? 'ceo-today-active' : 'ceo-today-empty' }}">{{ $dept['today_logs'] }}</span>
                    </td>
                    <td class="ceo-dept-td-center">
                        <span class="ceo-dept-num">{{ $dept['week_hours'] }}h</span>
                    </td>
                    <td class="ceo-dept-td-center">
                        @if(in_array($dept['work_mode'], ['github','hybrid']))
                        <span class="ceo-dept-num">{{ $dept['github_activity'] }}</span>
                        @else
                        <span class="ceo-dept-na">N/A</span>
                        @endif
                    </td>
                    <td class="ceo-dept-td-right">
                        <a href="{{ route('departments.show', $dept['id']) }}" class="dept-link-btn">View →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════
     SECTION 4 — Team Truth Table
═══════════════════════════════════════════ --}}
<div class="fv-card ceo-mb">
    <div class="fv-section-header">
        <div>
            <div class="fv-section-title">Team Output — The Full Picture</div>
            <div class="fv-section-sub">Every member. Every metric. No filters. Click a row to expand.</div>
        </div>
    </div>

    @if($members->isEmpty())
    <div class="ceo-member-empty">
        <div class="ceo-member-empty-icon">👥</div>
        <div class="ceo-member-empty-label">No team members yet</div>
        <div class="mt-sm">
            <a href="{{ route('team.invite') }}" class="ceo-info-link">Invite your first member →</a>
        </div>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="ceo-member-table">
            <thead>
                <tr>
                    <th class="ceo-member-th">Member</th>
                    <th class="ceo-member-th">Status</th>
                    <th class="ceo-member-th">GitHub</th>
                    <th class="ceo-member-th ceo-member-th-center">Output (30d)</th>
                    <th class="ceo-member-th ceo-member-th-center">Score</th>
                    <th class="ceo-member-th ceo-member-th-center">Performance</th>
                    <th class="ceo-member-th ceo-member-th-center">Trend</th>
                    <th class="ceo-member-th ceo-member-th-center">Flags</th>
                    <th class="ceo-member-th">Last Active</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $member)
                @php
                    $needsAttention = $member['pending_flags'] > 0 || $member['open_blockers'] > 0 || $member['commits'] === 0;
                    $perf = $member['performance_score'];
                    $perfSlug = match(true) {
                        $perf >= 5 => 'excellent',
                        $perf >= 3 => 'good',
                        $perf >= 1 => 'fair',
                        default    => 'needs-help',
                    };
                    $perfLabel = match($perfSlug) {
                        'excellent'  => 'Excellent',
                        'good'       => 'Good',
                        'fair'       => 'Fair',
                        default      => 'Needs Help',
                    };
                    $isStale = $member['last_activity']
                        ? $member['last_activity']->diffInDays(now()) > 7
                        : true;
                    $statusSlug = match(true) {
                        $member['status'] === 'on_leave' => 'leave',
                        $member['commits'] === 0 && $member['prs'] === 0 => 'inactive',
                        default => 'active',
                    };
                    $statusLabel = match($statusSlug) {
                        'leave'    => 'On Leave',
                        'inactive' => 'No Activity',
                        default    => 'Active',
                    };
                    $barW = min(100, round($member['avg_score'] * 10));
                @endphp
                <div x-data="{ expanded: false }" class="display-contents">
                <tr @click="expanded = !expanded"
                    class="ceo-member-row {{ $needsAttention ? 'ceo-member-row-flagged' : '' }}">

                    {{-- Member --}}
                    <td class="ceo-member-td">
                        <div class="ceo-member-cell">
                            <div class="ceo-member-avatar">{{ $member['avatar'] }}</div>
                            <div>
                                <div class="ceo-member-name">{{ $member['name'] }}</div>
                                <div class="ceo-member-email">{{ $member['email'] }}</div>
                                <span class="ceo-member-role-badge">{{ strtoupper($member['role']) }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- Status --}}
                    <td class="ceo-member-td">
                        <div class="ceo-status-cell" title="{{ $member['status_reason'] ?? '' }}">
                            <div class="member-status-dot status-dot-{{ $statusSlug }}"></div>
                            <span class="member-status-label">{{ $statusLabel }}</span>
                        </div>
                        @if($member['status_ends'])
                        <div class="member-status-ends">until {{ $member['status_ends']->format('M j') }}</div>
                        @endif
                    </td>

                    {{-- GitHub --}}
                    <td class="ceo-member-td">
                        @if($member['github_username'])
                        <a href="https://github.com/{{ $member['github_username'] }}" target="_blank"
                           @click.stop class="member-gh-link">
                            {{ $member['github_username'] }}
                        </a>
                        @else
                        <span class="member-gh-none">Not connected</span>
                        @endif
                    </td>

                    {{-- Output --}}
                    <td class="ceo-member-td-center">
                        <div class="ceo-output-cell">
                            <div class="text-center">
                                <div class="member-output-num">{{ $member['commits'] }}</div>
                                <div class="member-output-label">commits</div>
                            </div>
                            <div class="ceo-output-divider"></div>
                            <div class="text-center">
                                <div class="member-output-num-green">{{ $member['prs'] }}</div>
                                <div class="member-output-label">PRs</div>
                            </div>
                        </div>
                    </td>

                    {{-- Score --}}
                    <td class="ceo-member-td-center">
                        <div class="member-score-num">{{ number_format($member['avg_score'], 1) }}</div>
                        <div class="ceo-score-bar-wrap">
                            <progress class="member-score-bar" value="{{ $barW }}" max="100"></progress>
                        </div>
                    </td>

                    {{-- Performance --}}
                    <td class="ceo-member-td-center">
                        <div class="perf-badge perf-badge-{{ $perfSlug }}">
                            <span class="perf-badge-score perf-{{ $perfSlug }}">{{ $perf }}</span>
                            <span class="perf-badge-label perf-{{ $perfSlug }}">{{ $perfLabel }}</span>
                        </div>
                    </td>

                    {{-- Trend --}}
                    <td class="ceo-member-td-center">
                        @if($member['trend'] === 'up')
                            <span class="member-trend-up" title="Improving this week">▲</span>
                        @elseif($member['trend'] === 'down')
                            <span class="member-trend-down" title="Declining this week">▼</span>
                        @else
                            <span class="member-trend-flat" title="Stable">—</span>
                        @endif
                    </td>

                    {{-- Flags & Blockers --}}
                    <td class="ceo-member-td-center">
                        @if($member['pending_flags'] > 0 || $member['open_blockers'] > 0)
                        <div class="ceo-flags-cell">
                            @if($member['pending_flags'] > 0)
                            <span class="fv-badge fv-badge-error">{{ $member['pending_flags'] }} flag</span>
                            @endif
                            @if($member['open_blockers'] > 0)
                            <span class="fv-badge fv-badge-warning">{{ $member['open_blockers'] }} blocked</span>
                            @endif
                        </div>
                        @else
                        <span class="ceo-clear-check" title="All clear">✓</span>
                        @endif
                    </td>

                    {{-- Last Active --}}
                    <td class="ceo-member-td">
                        @if($member['last_activity'])
                        <div class="{{ $isStale ? 'member-last-active-stale' : 'member-last-active' }}">
                            {{ $member['last_activity']->diffForHumans() }}
                        </div>
                        @if($isStale)
                        <div class="member-inactive-note">Inactive {{ $member['last_activity']->diffInDays(now()) }}d</div>
                        @endif
                        @else
                        <span class="member-never">Never</span>
                        @endif
                    </td>
                </tr>

                {{-- Expanded Row --}}
                <tr x-show="expanded" x-transition class="ceo-expanded-row">
                    <td colspan="9">
                        <div class="ceo-expanded-inner">

                            {{-- Recent Activity --}}
                            <div>
                                <div class="ceo-expanded-section-label">Recent Activity</div>
                                @forelse($member['recent_activities'] as $act)
                                <div class="ceo-act-item">
                                    <span class="{{ $act->event_type === 'commit' ? 'ceo-act-type-commit' : 'ceo-act-type-pr' }}">
                                        {{ $act->event_type }}
                                    </span>
                                    <span class="ceo-act-message">
                                        {{ $act->metadata['message'] ?? $act->metadata['title'] ?? 'Activity' }}
                                    </span>
                                    <span class="ceo-act-time">{{ $act->occurred_at->diffForHumans() }}</span>
                                </div>
                                @empty
                                <div class="ceo-act-empty">No recent activity</div>
                                @endforelse
                            </div>

                            {{-- Open Blockers --}}
                            <div>
                                <div class="ceo-expanded-section-label">Open Blockers</div>
                                @forelse($member['open_blocker_list'] as $b)
                                <div class="ceo-blocker-card">
                                    <div class="ceo-blocker-title">{{ $b->title }}</div>
                                    <div class="ceo-blocker-meta">
                                        {{ $b->blockingUser ? 'Blocked by ' . $b->blockingUser->name : 'No specific blocker' }}
                                        · {{ $b->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                @empty
                                <div class="ceo-all-clear">✓ No open blockers</div>
                                @endforelse
                            </div>

                            {{-- Pending Flags --}}
                            <div>
                                <div class="ceo-expanded-section-label">Fairness Flags</div>
                                @forelse($member['pending_flag_list'] as $f)
                                <div class="ceo-flag-card">
                                    <div class="ceo-flag-header">
                                        <span class="ceo-flag-type">{{ str_replace('_', ' ', $f->flag_type) }}</span>
                                        <span class="ceo-flag-conf">{{ round($f->confidence_score * 100) }}%</span>
                                    </div>
                                    <div class="ceo-flag-time">{{ $f->created_at->diffForHumans() }}</div>
                                </div>
                                @empty
                                <div class="ceo-all-clear">✓ No pending flags</div>
                                @endforelse
                            </div>

                        </div>
                    </td>
                </tr>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════
     SECTION 4b — Charts
═══════════════════════════════════════════ --}}

{{-- Team Performance Bar Chart --}}
<div class="fv-card p-lg ceo-mb">
    <div class="ceo-chart-header">
        <div>
            <div class="fv-section-title">Team Performance Comparison</div>
            <div class="fv-section-sub">Commits (30d) and Performance Score per member</div>
        </div>
        <div class="chart-legend-wrap">
            <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-black"></span>Commits</span>
            <span class="chart-legend-item"><span class="chart-legend-dot chart-legend-dot-gray"></span>Score</span>
        </div>
    </div>
    <canvas id="teamChart" height="60"></canvas>
</div>

{{-- Distribution Doughnut + 7-day Trend --}}
<div class="ceo-charts-two-col">

    {{-- Performance Distribution --}}
    <div class="fv-card p-lg">
        <div class="fv-section-title mb-xs">Performance Distribution</div>
        <div class="fv-section-sub mb-lg">Members by performance tier</div>
        <div class="ceo-dist-wrap">
            <div class="ceo-dist-chart-box">
                <canvas id="distChart" width="180" height="180"></canvas>
                <div class="ceo-dist-chart-center">
                    <div class="ceo-dist-total">{{ $members->count() }}</div>
                    <div class="ceo-dist-label">members</div>
                </div>
            </div>
            <div class="ceo-dist-legend">
                @foreach([['Excellent','excellent',$excellent],['Good','good',$good],['Fair','fair',$fair],['Needs Help','needs-help',$needsHelp]] as [$label,$slug,$count])
                <div class="flex-between">
                    <div class="flex-center-gap-sm">
                        <div class="ceo-dist-legend-dot ceo-dist-dot-{{ $slug }}"></div>
                        <span class="ceo-dist-legend-label">{{ $label }}</span>
                    </div>
                    <span class="ceo-dist-legend-count">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 7-Day Trend --}}
    <div class="fv-card p-lg">
        <div class="fv-section-title mb-xs">Team Activity Trend</div>
        <div class="fv-section-sub mb-lg">Total events — last 7 days</div>
        <canvas id="trendChart" height="140"></canvas>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     SECTION 5 — Two Column Layout
═══════════════════════════════════════════ --}}
<div class="dash-two-col-equal">

    {{-- LEFT — Recent Fairness Flags --}}
    @if($modules['fairness_engine'] ?? true)
    <div class="fv-card">
        <div class="ceo-section-card">
            <h3 class="ceo-section-card-title">Flags Needing Your Review</h3>
            <a href="{{ route('fairness.index') }}" class="ceo-section-link">View all →</a>
        </div>
        <div class="ceo-flag-list">
            @forelse($recentFlags as $flag)
            @php $fSeverity = $flag->severity ?? 'medium'; @endphp
            <div class="ceo-flag-item">
                <div class="ceo-flag-item-inner">
                    <div class="ceo-flag-row">
                        <span class="ceo-flag-severity-badge ceo-flag-severity-{{ $fSeverity }}">
                            {{ str_replace('_', ' ', $flag->flag_type) }}
                        </span>
                        <span class="ceo-flag-confidence">{{ round($flag->confidence_score * 100) }}% confidence</span>
                    </div>
                    <div class="ceo-flag-user">{{ $flag->flaggedUser?->name ?? 'Team' }}</div>
                    <div class="ceo-flag-time2">{{ $flag->created_at->diffForHumans() }}</div>
                </div>
                <a href="{{ route('fairness.index') }}" class="ceo-review-btn">Review</a>
            </div>
            @empty
            <div class="ceo-empty-flags">
                <div class="ceo-empty-icon">✓</div>
                <div class="ceo-empty-text">No pending fairness flags</div>
            </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- RIGHT — Open Blockers --}}
    @if($modules['blockers'] ?? true)
    <div class="fv-card">
        <div class="ceo-section-card">
            <h3 class="ceo-section-card-title">Active Blockers</h3>
            <a href="{{ route('dependency.index') }}" class="ceo-section-link">View all →</a>
        </div>
        <div class="ceo-flag-list">
            @forelse($recentBlockers as $blocker)
            @php $bPriority = $blocker->priority ?? 'low'; @endphp
            <div class="ceo-blocker-item">
                <div class="ceo-blocker-item-inner">
                    <div class="ceo-flag-row">
                        <span class="blocker-priority-badge blocker-priority-{{ $bPriority }}">{{ $bPriority }}</span>
                        <span class="ceo-blocker-age">{{ $blocker->ageInDays() }}d old</span>
                    </div>
                    <div class="ceo-blocker-title-main">{{ $blocker->title }}</div>
                    <div class="ceo-blocker-who">
                        {{ $blocker->blockedUser?->name ?? 'Unknown' }}
                        @if($blocker->project) · {{ $blocker->project->name }}@endif
                    </div>
                </div>
                <a href="{{ route('dependency.index') }}" class="ceo-resolve-btn">Resolve</a>
            </div>
            @empty
            <div class="ceo-empty-flags">
                <div class="ceo-empty-icon">✓</div>
                <div class="ceo-empty-text">No open blockers</div>
            </div>
            @endforelse
        </div>
    </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════
     SECTION 6 — Quick Actions
═══════════════════════════════════════════ --}}
<div class="ceo-quick-grid">

    @if($modules['fairness_engine'] ?? true)
    <div class="fv-card ceo-quick-card">
        <div class="fv-stat-icon ceo-quick-icon">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
        </div>
        <div class="ceo-quick-title">Run Fairness Analysis</div>
        <div class="ceo-quick-desc">Detect workload imbalances and bias patterns</div>
        <form method="POST" action="{{ route('fairness.run') }}">
            @csrf
            <button type="submit" class="fv-btn fv-btn-primary w-full btn-center">Run Now</button>
        </form>
    </div>
    @endif

    @if($modules['github_sync'] ?? true)
    <div class="fv-card ceo-quick-card">
        <div class="fv-stat-icon ceo-quick-icon">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </div>
        <div class="ceo-quick-title">Sync GitHub</div>
        <div class="ceo-quick-desc">Pull latest commits and pull requests</div>
        <form method="POST" action="{{ route('dashboard.sync-github') }}">
            @csrf
            <button type="submit" class="fv-btn fv-btn-secondary w-full btn-center">Sync Now</button>
        </form>
    </div>
    @endif

    <div class="fv-card ceo-quick-card">
        <div class="fv-stat-icon ceo-quick-icon">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        </div>
        <div class="ceo-quick-title">Invite Member</div>
        <div class="ceo-quick-desc">Add a new engineer to your team</div>
        <a href="{{ route('team.invite') }}" class="fv-btn fv-btn-outline w-full btn-center">Invite</a>
    </div>

    @if($modules['ai_intelligence'] ?? true)
    <div class="fv-card ceo-quick-card">
        <div class="fv-stat-icon ceo-quick-icon">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        </div>
        <div class="ceo-quick-title">AI Intelligence</div>
        <div class="ceo-quick-desc">Ask questions about your team's output</div>
        <a href="{{ route('ai.dashboard') }}" class="fv-btn fv-btn-secondary w-full btn-center">Open AI</a>
    </div>
    @endif

</div>

</div>{{-- /page-wrapper --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('teamChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($chartMembers) !!},
        datasets: [
            { label: 'Commits (30d)', data: {!! json_encode($chartCommits) !!}, backgroundColor: '#18181b', borderRadius: 4 },
            { label: 'Performance Score', data: {!! json_encode($chartScores) !!}, backgroundColor: '#71717a', borderRadius: 4, yAxisID: 'y1' }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 12, family: "'Plus Jakarta Sans',sans-serif" }, color: '#71717a' } } },
        scales: {
            y:  { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { color: '#a1a1aa' } },
            y1: { beginAtZero: true, position: 'right', max: 100, grid: { display: false }, ticks: { color: '#a1a1aa' } }
        }
    }
});

new Chart(document.getElementById('distChart'), {
    type: 'doughnut',
    data: {
        labels: ['Excellent','Good','Fair','Needs Help'],
        datasets: [{ data: [{{ $excellent }},{{ $good }},{{ $fair }},{{ $needsHelp }}], backgroundColor: ['#18181b','#52525b','#a1a1aa','#e4e4e7'], borderWidth: 0, hoverOffset: 4 }]
    },
    options: { responsive: false, plugins: { legend: { display: false } }, cutout: '65%' }
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($trendLabels->values()) !!},
        datasets: [{
            label: 'Activities',
            data: {!! json_encode($trendCounts->values()) !!},
            borderColor: '#18181b',
            backgroundColor: 'rgba(24,24,27,0.06)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#18181b',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { color: '#a1a1aa' } },
            x: { grid: { display: false }, ticks: { color: '#a1a1aa' } }
        }
    }
});

function updateClock() {
    const now = new Date();
    const dateEl = document.getElementById('live-date');
    const timeEl = document.getElementById('live-time');
    if (dateEl) {
        dateEl.textContent = now.toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }
    if (timeEl) {
        timeEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
@endpush
@endsection
