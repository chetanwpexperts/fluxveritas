@extends('layouts.app')
@section('title', 'Sprints')
@section('content')

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Sprints</h1>
        <p class="page-subtitle">Manage your team's sprint cycles</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('sprints.create') }}" class="btn-primary">+ New Sprint</a>
    </div>
</div>

    {{-- PROJECT FILTER --}}
    <form method="GET" style="margin-bottom:20px;">
        <select name="project_id" onchange="this.form.submit()" style="padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;min-width:200px;cursor:pointer;">
            <option value="">All Projects</option>
            @foreach($projects as $p)
            <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </form>

    {{-- SUCCESS FLASH --}}
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:0.82rem;color:#16a34a;font-weight:600;">
        {{ session('success') }}
    </div>
    @endif

    {{-- SPRINTS LIST --}}
    <div id="sprints-list">
    @if(count($sprints) === 0)
    <div style="text-align:center;padding:60px 24px;color:#71717a;background:white;border:1px solid #e4e4e7;border-radius:12px;">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 16px;display:block;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6.75v6.75"/>
        </svg>
        <div style="font-size:1rem;font-weight:600;color:#374151;margin-bottom:8px;">No sprints yet</div>
        <div style="font-size:0.875rem;margin-bottom:20px;">Create your first sprint to start organizing work into focused cycles.</div>
        <a href="{{ route('sprints.create') }}" style="padding:8px 18px;background:#09090b;color:white;border-radius:8px;text-decoration:none;font-size:0.82rem;font-weight:700;">Create First Sprint</a>
    </div>
    @else
    @php
        $grouped = collect($sprints)->groupBy('status');
        $order = ['active', 'planning', 'completed'];
        $groupLabels = [
            'active'    => '🟢 Active Sprints',
            'planning'  => '📋 Planning',
            'completed' => '✅ Completed',
        ];
        $borderColors = [
            'active'    => '#16a34a',
            'planning'  => '#3b82f6',
            'completed' => '#a1a1aa',
        ];
        $badgeBg = [
            'active'    => '#dcfce7',
            'planning'  => '#dbeafe',
            'completed' => '#f4f4f5',
        ];
        $badgeText = [
            'active'    => '#16a34a',
            'planning'  => '#1d4ed8',
            'completed' => '#71717a',
        ];
    @endphp

    @foreach($order as $statusKey)
        @if($grouped->has($statusKey))
        <div style="font-size:0.72rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;margin-top:20px;">
            {{ $groupLabels[$statusKey] }}
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:8px;">
        @foreach($grouped[$statusKey] as $sprint)
        @php
            $borderColor = $borderColors[$statusKey] ?? '#e4e4e7';
            $bg   = $badgeBg[$statusKey]   ?? '#f4f4f5';
            $text = $badgeText[$statusKey] ?? '#71717a';
        @endphp
        <div style="background:white;border:1px solid #e4e4e7;border-left:4px solid {{ $borderColor }};border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;">

            {{-- TOP ROW --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <div style="font-weight:800;font-size:0.95rem;color:#09090b;">{{ $sprint['name'] }}</div>
                    <div style="font-size:0.75rem;color:#71717a;margin-top:2px;">{{ $sprint['project']->name }}</div>
                </div>
                <span style="background:{{ $bg }};color:{{ $text }};padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;white-space:nowrap;">{{ ucfirst($sprint['status']) }}</span>
            </div>

            {{-- GOAL ROW --}}
            @if(!empty($sprint['goal']))
            <div style="font-style:italic;font-size:0.8rem;color:#71717a;padding:0;">{{ $sprint['goal'] }}</div>
            @endif

            {{-- DATE ROW --}}
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                <span style="font-size:0.8rem;color:#3f3f46;">
                    📅 {{ $sprint['start_date'] ? $sprint['start_date']->format('M j') : '—' }} → {{ $sprint['end_date'] ? $sprint['end_date']->format('M j, Y') : '—' }}
                </span>
                @if($sprint['status'] === 'active')
                    @if($sprint['days_remaining'] >= 0)
                    <span style="font-size:0.8rem;color:#16a34a;font-weight:600;">⏳ {{ $sprint['days_remaining'] }} days left</span>
                    @else
                    <span style="font-size:0.8rem;color:#dc2626;font-weight:600;">⚠ {{ abs($sprint['days_remaining']) }} days overdue</span>
                    @endif
                @elseif($sprint['status'] === 'planning')
                <span style="font-size:0.8rem;color:#71717a;">Starts {{ $sprint['start_date'] ? $sprint['start_date']->diffForHumans() : '' }}</span>
                @elseif($sprint['status'] === 'completed')
                <span style="font-size:0.8rem;color:#71717a;">Completed</span>
                @endif
            </div>

            {{-- PROGRESS ROW --}}
            <div>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <span style="font-size:0.75rem;color:#71717a;display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#a1a1aa;"></span>
                        {{ $sprint['total_tasks'] }} Total
                    </span>
                    <span style="font-size:0.75rem;color:#71717a;display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;"></span>
                        {{ $sprint['completed_tasks'] }} Done
                    </span>
                    <span style="font-size:0.75rem;color:#71717a;display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;"></span>
                        {{ $sprint['in_progress_tasks'] }} In Progress
                    </span>
                    <span style="font-size:0.75rem;color:#71717a;display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#ef4444;"></span>
                        {{ $sprint['blocked_tasks'] }} Blocked
                    </span>
                </div>
                <div style="height:5px;background:#f4f4f5;border-radius:99px;overflow:hidden;margin-top:6px;">
                    <div style="height:100%;background:#10b981;border-radius:99px;width:{{ $sprint['completion_pct'] }}%;transition:width 0.3s;"></div>
                </div>
                <div style="font-size:0.68rem;color:#a1a1aa;margin-top:4px;">{{ $sprint['completion_pct'] }}% complete</div>
            </div>

            {{-- ACTIONS ROW --}}
            <div style="display:flex;gap:8px;align-items:center;margin-top:4px;flex-wrap:wrap;">
                <a href="/tasks?sprint_id={{ $sprint['id'] }}" style="border:1px solid #e4e4e7;background:white;padding:5px 12px;border-radius:6px;font-size:0.75rem;font-weight:600;color:#3f3f46;text-decoration:none;">View Tasks</a>
                <a href="{{ route('sprints.show', $sprint['id']) }}" style="border:1px solid #e4e4e7;background:white;padding:5px 12px;border-radius:6px;font-size:0.75rem;font-weight:600;color:#3f3f46;text-decoration:none;">Details</a>

                @if($sprint['status'] === 'planning')
                <form method="POST" action="{{ route('sprints.start', $sprint['id']) }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="padding:5px 14px;background:#16a34a;color:white;border:none;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer;">▶ Start Sprint</button>
                </form>
                @endif

                @if($sprint['status'] === 'active')
                <form method="POST" action="{{ route('sprints.complete', $sprint['id']) }}" style="display:inline;" onsubmit="return confirm('Complete this sprint? Incomplete tasks will move to backlog.')">
                    @csrf
                    <button type="submit" style="padding:5px 14px;background:#f59e0b;color:white;border:none;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer;">✓ Complete Sprint</button>
                </form>
                @endif
            </div>

        </div>
        @endforeach
        </div>
        @endif
    @endforeach
    @endif
    </div>{{-- #sprints-list --}}

    <div id="sprints-pagination" class="pagination-wrapper"></div>

</div>

@push('scripts')
<script src="{{ asset('js/sprints-page.js') }}"></script>
@endpush
@endsection
