@extends('layouts.app')

@section('title', '🏥 System Health')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/health.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Overall Status Banner --}}
    <div class="gh-banner {{ $overallStatus }}">
        <div class="gh-banner-dot"></div>
        @if($overallStatus === 'healthy')
            ✅ All Systems Operational
        @elseif($overallStatus === 'warning')
            ⚠️ Warning — Review Required
        @else
            🚨 Critical Issues Detected — Immediate Action Needed
        @endif
        <span class="gh-banner-meta">Last checked: {{ $checkedAt }}</span>
        <form method="POST" action="{{ route('agent.run') }}" class="agent-run-form">
            @csrf
            <button type="submit" id="run-btn" class="gh-run-btn">
                🔄 Run Check Now
            </button>
        </form>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="gh-flash success">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="gh-flash error">✗ {{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="gh-header">
        <div>
            <div class="gh-title">🏥 System Health Dashboard</div>
            <div class="gh-sub">Real-time monitoring of all system components · OutraqHQ System Guardian</div>
        </div>
        <a href="{{ route('agent.health') }}" class="gh-refresh-btn">↺ Refresh</a>
    </div>

    {{-- ── Health Cards ── --}}
    @php
    $cardDefs = [
        'database'       => ['icon' => '🗄️',  'name' => 'Database'],
        'storage'        => ['icon' => '💾',   'name' => 'Storage'],
        'cache'          => ['icon' => '⚡',   'name' => 'Cache'],
        'queue'          => ['icon' => '📬',   'name' => 'Queue'],
        'memory'         => ['icon' => '🧠',   'name' => 'Memory'],
        'logs'           => ['icon' => '📋',   'name' => 'Error Logs'],
        'data_integrity' => ['icon' => '🔗',   'name' => 'Data Integrity'],
        'view_cache'     => ['icon' => '🖼️',  'name' => 'View Cache'],
        'ollama'         => ['icon' => '🤖',   'name' => 'Ollama AI'],
    ];
    @endphp

    <div>
        <div class="gh-section-label">Component Health</div>
        <div class="gh-grid">
            @foreach($cardDefs as $key => $def)
            @php $check = $health[$key] ?? ['status' => 'unknown', 'message' => 'Not checked']; $st = $check['status'] ?? 'unknown'; @endphp
            <div class="gh-card {{ $st }}">
                <div class="gh-card-header">
                    <div class="gh-card-icon {{ $st }}">{{ $def['icon'] }}</div>
                    <span class="gh-badge {{ $st }}">{{ $st }}</span>
                </div>
                <div class="gh-card-name">{{ $def['name'] }}</div>

                @if($key === 'storage' && isset($check['used_percent']))
                <div class="gh-card-value">{{ $check['used_percent'] }}<span class="gh-card-unit">%</span></div>
                <div class="gh-card-msg">{{ $check['free_gb'] ?? '?' }} GB free</div>
                <div class="gh-bar-wrap">
                    <progress class="gh-progress gh-progress-{{ $st }}" value="{{ $check['used_percent'] }}" max="100"></progress>
                </div>

                @elseif($key === 'database' && isset($check['size_mb']))
                <div class="gh-card-value">{{ $check['size_mb'] }}<span class="gh-card-unit">MB</span></div>
                <div class="gh-card-msg">{{ $check['message'] }}</div>

                @elseif($key === 'queue')
                <div class="gh-card-metrics">
                    <div>
                        <div class="gh-card-stat-num gh-val-danger">{{ $check['failed_jobs'] ?? 0 }}</div>
                        <div class="gh-card-stat-label">failed</div>
                    </div>
                    <div>
                        <div class="gh-card-stat-num gh-val-normal">{{ $check['pending_jobs'] ?? 0 }}</div>
                        <div class="gh-card-stat-label">pending</div>
                    </div>
                </div>
                <div class="gh-card-msg">{{ $check['message'] }}</div>

                @elseif($key === 'memory')
                <div class="gh-card-value">{{ $check['usage_mb'] ?? 0 }}<span class="gh-card-unit">MB</span></div>
                <div class="gh-card-msg">Limit: {{ $check['limit'] ?? 'unknown' }}</div>

                @elseif($key === 'logs')
                <div class="gh-card-metrics">
                    <div>
                        <div class="gh-card-stat-num {{ ($check['recent_errors'] ?? 0) > 0 ? 'gh-val-danger' : 'gh-val-normal' }}">{{ $check['recent_errors'] ?? 0 }}</div>
                        <div class="gh-card-stat-label">errors</div>
                    </div>
                    <div>
                        <div class="gh-card-stat-num gh-val-normal">{{ $check['size_mb'] ?? 0 }}</div>
                        <div class="gh-card-stat-label">MB</div>
                    </div>
                </div>
                <div class="gh-card-msg">{{ $check['message'] }}</div>

                @elseif($key === 'data_integrity')
                <div class="gh-card-metrics">
                    <div>
                        <div class="gh-card-stat-num {{ ($check['orphan_users'] ?? 0) > 0 ? 'gh-val-amber' : 'gh-val-normal' }}">{{ $check['orphan_users'] ?? 0 }}</div>
                        <div class="gh-card-stat-label">orphan users</div>
                    </div>
                    <div>
                        <div class="gh-card-stat-num {{ ($check['orphan_activities'] ?? 0) > 0 ? 'gh-val-amber' : 'gh-val-normal' }}">{{ $check['orphan_activities'] ?? 0 }}</div>
                        <div class="gh-card-stat-label">orphan activities</div>
                    </div>
                </div>
                <div class="gh-card-msg">{{ $check['message'] }}</div>

                @elseif($key === 'cache' && isset($check['driver']))
                <div class="gh-card-value">{{ strtoupper($check['driver'] ?? '—') }}</div>
                <div class="gh-card-msg">{{ $check['message'] }}</div>

                @else
                <div class="gh-card-msg mt-sm">{{ $check['message'] }}</div>
                @endif
            </div>
            @endforeach

            {{-- Security summary card --}}
            @php
            $secCritical = count(array_filter($security, fn($i) => $i['severity'] === 'critical'));
            $secHigh     = count(array_filter($security, fn($i) => $i['severity'] === 'high'));
            $secTotal    = count($security);
            $secSt       = $secCritical > 0 ? 'critical' : ($secHigh > 0 ? 'warning' : 'healthy');
            @endphp
            <div class="gh-card {{ $secSt }}">
                <div class="gh-card-header">
                    <div class="gh-card-icon {{ $secSt }}">🔐</div>
                    <span class="gh-badge {{ $secSt }}">{{ $secSt }}</span>
                </div>
                <div class="gh-card-name">Security</div>
                <div class="gh-card-value">{{ $secTotal }}<span class="gh-card-unit"> issue{{ $secTotal !== 1 ? 's' : '' }}</span></div>
                <div class="gh-card-msg">{{ $secCritical > 0 ? "{$secCritical} critical" : ($secTotal === 0 ? 'No issues found' : "{$secHigh} high severity") }}</div>
                <div class="gh-card-stat-label mt-sm">Scanned: {{ $scannedAt }}</div>
            </div>
        </div>
    </div>

    {{-- ── System Actions ── --}}
    <div class="gh-section">
        <div class="gh-section-head">
            <div>
                <div class="gh-section-title">⚙️ System Actions</div>
                <div class="gh-section-sub">Run maintenance tasks directly from the dashboard</div>
            </div>
        </div>
        <div class="gh-section-body">
            <div class="gh-actions">
                <form method="POST" action="{{ route('agent.cache.clear') }}">
                    @csrf
                    <button type="submit" class="gh-action-btn"
                            data-confirm="Clear all caches?">
                        <div class="gh-action-icon">🧹</div>
                        <div class="gh-action-name">Clear All Cache</div>
                        <div class="gh-action-desc">Clears cache, views, routes, and config</div>
                    </button>
                </form>
                <form method="POST" action="{{ route('agent.logs.clear') }}">
                    @csrf
                    <button type="submit" class="gh-action-btn"
                            data-confirm="Clear the log file? This cannot be undone.">
                        <div class="gh-action-icon">📋</div>
                        <div class="gh-action-name">Clear Log File</div>
                        <div class="gh-action-desc">Empties laravel.log (truncates to 0 bytes)</div>
                    </button>
                </form>
                <form method="POST" action="{{ route('agent.optimize') }}">
                    @csrf
                    <button type="submit" class="gh-action-btn">
                        <div class="gh-action-icon">⚡</div>
                        <div class="gh-action-name">Optimize App</div>
                        <div class="gh-action-desc">Caches config, routes, and views for production</div>
                    </button>
                </form>
                <button type="button" class="gh-action-btn" data-action="agent-run-full">
                    <div class="gh-action-icon">🔄</div>
                    <div class="gh-action-name">Run Agent Now</div>
                    <div class="gh-action-desc">Runs all team checks + health + security scan</div>
                </button>
            </div>

            <div id="run-result">
                <div id="run-result-title">🤖 Agent Run Results</div>
                <div id="run-lines"></div>
            </div>
        </div>
    </div>

    {{-- ── Security Scan ── --}}
    <div class="gh-section">
        <div class="gh-section-head">
            <div>
                <div class="gh-section-title">🔐 Security Scan Results</div>
                <div class="gh-section-sub">Last scanned: {{ $scannedAt }}</div>
            </div>
            <form method="POST" action="{{ route('agent.run') }}" class="agent-run-form">
                @csrf
                <button type="submit" class="gh-section-btn">Run Security Scan</button>
            </form>
        </div>
        <div class="gh-section-body gh-section-body-flush">
            @if(empty($security))
            <div class="gh-security-empty">
                <div class="gh-security-empty-icon">✅</div>
                <div class="gh-security-empty-title">No security issues found</div>
                <div class="gh-security-empty-sub">All security checks passed. Keep APP_DEBUG=false in production.</div>
            </div>
            @else
            <div class="gh-table-wrap">
                <table class="gh-table">
                    <thead><tr>
                        <th>Severity</th>
                        <th>Issue</th>
                        <th>Recommended Fix</th>
                    </tr></thead>
                    <tbody>
                        @foreach($security as $issue)
                        <tr>
                            <td><span class="sev-{{ $issue['severity'] }}">{{ strtoupper($issue['severity']) }}</span></td>
                            <td>{{ $issue['issue'] }}</td>
                            <td class="gh-table-fix">{{ $issue['fix'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Recent Agent Actions ── --}}
    <div class="gh-section">
        <div class="gh-section-head">
            <div>
                <div class="gh-section-title">📜 Recent Agent Activity</div>
                <div class="gh-section-sub">Last 20 actions taken by the System Guardian</div>
            </div>
        </div>
        <div class="gh-table-wrap">
            @if($recentActions->isEmpty())
            <div class="gh-security-empty">
                <div class="gh-security-empty-sub">No agent activity yet. Run the agent to generate data.</div>
            </div>
            @else
            <table class="gh-table">
                <thead><tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Priority</th>
                </tr></thead>
                <tbody>
                    @foreach($recentActions as $action)
                    <tr>
                        <td class="gh-table-muted">{{ $action->created_at->diffForHumans() }}</td>
                        <td><span class="gh-table-type">{{ str_replace('_', ' ', $action->notification_type) }}</span></td>
                        <td class="gh-table-title">{{ $action->title }}</td>
                        <td><span class="gh-priority gh-priority-{{ $action->priority }}">{{ strtoupper($action->priority) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    window.HEALTH_CSRF = '{{ csrf_token() }}';
    window.AGENT_RUN_URL = '{{ route("agent.run") }}';
</script>
<script src="{{ asset('js/health.js') }}"></script>
@endpush
