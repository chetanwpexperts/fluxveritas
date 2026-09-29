@extends('layouts.app')
@section('title', 'Team Work Log')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/worklog.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Team Work Log</h1>
            <p class="page-subtitle">Daily work log overview for all team members</p>
        </div>
        <div class="page-header-right">
            <form method="GET" action="{{ route('worklog.team') }}"
                  style="display:flex;gap:8px;align-items:center;">
                <input type="date" name="date" value="{{ $date }}"
                       class="task-filter-select" onchange="this.form.submit()">
                <a href="{{ route('worklog.index') }}" class="btn-secondary">My Log</a>
            </form>
        </div>
    </div>

    {{-- SUMMARY STATS --}}
    <div class="dash-grid-4 mb-md">
        <div class="fv-stat">
            <div class="fv-stat-number">{{ $totalLogged }}/{{ $members->count() }}</div>
            <div class="fv-stat-label">Logged Today</div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-number">{{ $totalHours }}h</div>
            <div class="fv-stat-label">Total Hours</div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-number">{{ $avgQuality }}/10</div>
            <div class="fv-stat-label">Avg Quality</div>
        </div>
        <div class="fv-stat {{ $notLogged->count() > 0 ? 'fv-stat-warn' : '' }}">
            <div class="fv-stat-number">{{ $notLogged->count() }}</div>
            <div class="fv-stat-label">Not Logged</div>
        </div>
    </div>

    {{-- NOT LOGGED WARNING --}}
    @if($notLogged->count() > 0)
    <div class="fv-alert fv-alert-warning mb-md">
        ⚠️ Not logged today: {{ $notLogged->pluck('name')->join(', ') }}
    </div>
    @endif

    {{-- TEAM MEMBERS LOG TABLE --}}
    <div class="fv-card">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">Team Activity</div>
                <div class="fv-section-sub">
                    {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                </div>
            </div>
        </div>

        <table class="fv-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Status</th>
                    <th>Hours</th>
                    <th>Entries</th>
                    <th>Avg Quality</th>
                    <th>Last Entry</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $member)
                @php
                    $logs      = $member->workLogs;
                    $hasLogged = $logs->count() > 0;
                    $hours     = round($logs->sum('duration_minutes') / 60, 1);
                    $quality   = round($logs->avg('output_value') ?? 0, 1);
                    $lastLog   = $logs->sortByDesc('created_at')->first();
                @endphp
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="tr-avatar" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fv-fw-600">{{ $member->name }}</div>
                                <div class="text-muted-xs">{{ $member->job_title ?? $member->designation ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($hasLogged)
                        <span class="fv-badge" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;">
                            ✅ Logged
                        </span>
                        @else
                        <span class="fv-badge" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
                            ❌ Not logged
                        </span>
                        @endif
                    </td>
                    <td class="fv-fw-600">{{ $hasLogged ? $hours . 'h' : '—' }}</td>
                    <td>{{ $hasLogged ? $logs->count() : '—' }}</td>
                    <td>
                        @if($hasLogged && $quality > 0)
                        <span class="{{ $quality >= 7 ? 'text-green' : ($quality >= 5 ? 'text-amber' : 'text-red') }}">
                            {{ $quality }}/10
                        </span>
                        @else
                        —
                        @endif
                    </td>
                    <td class="text-muted-xs">
                        {{ $lastLog ? $lastLog->created_at->format('h:i A') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
