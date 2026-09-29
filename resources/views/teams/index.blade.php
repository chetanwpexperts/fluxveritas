@extends('layouts.app')
@section('title', 'Teams')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">
                @if(($viewMode ?? 'all') === 'all') All Teams
                @else Department Teams
                @endif
            </h1>
            <p class="page-subtitle">
                {{ $teams->count() }} teams &middot;
                {{ $teams->sum(fn($t) => $t->members->count()) }} total members
            </p>
        </div>
        @if(auth()->user()->hasAnyRole(['admin','owner','ceo','super_admin']))
        <div class="page-header-right">
            <a href="{{ route('teams.create') }}" class="btn-primary">+ Create Team</a>
        </div>
        @endif
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="fv-alert fv-alert-error mb-md">❌ {{ session('error') }}</div>
    @endif

    @if($teams->isEmpty())
    <div class="fv-empty">
        <div style="font-size:48px;margin-bottom:16px">👥</div>
        <div class="fv-empty-title">No teams yet</div>
        <div class="fv-empty-desc">Create your first team to organize your employees.</div>
        @if(auth()->user()->hasAnyRole(['admin','owner','ceo','super_admin']))
        <a href="{{ route('teams.create') }}" class="btn-primary" style="margin-top:16px">+ Create First Team</a>
        @endif
    </div>
    @else

    @foreach($teams->groupBy('department_id') as $deptId => $deptTeams)
    @php $dept = $deptTeams->first()->department; @endphp

    <div class="team-dept-group">
        <div class="team-dept-header">
            <div class="team-dept-name">{{ $dept->name ?? 'No Department' }}</div>
            <div class="team-dept-count">{{ $deptTeams->count() }} team(s)</div>
        </div>

        <div class="team-cards-grid">
            @foreach($deptTeams as $team)
            @php
                $memberCount  = $team->members->count();
                $loggedToday  = $team->getLoggedTodayCount();
                $activeTasks  = $team->getActiveTasksCount();
                $capacityPct  = round(($memberCount / 10) * 100);
                $isAtCapacity = $memberCount >= 10;
                $belowMinimum = $memberCount < 3;
            @endphp

            <a href="{{ route('teams.show', $team->id) }}" class="team-card">
                <div class="team-card-header">
                    <div class="team-card-title">{{ $team->name }}</div>
                    @if($isAtCapacity)
                        <span class="team-badge team-badge-full">Full</span>
                    @elseif($belowMinimum)
                        <span class="team-badge team-badge-warn">Needs members</span>
                    @else
                        <span class="team-badge team-badge-active">Active</span>
                    @endif
                </div>

                @if($team->description)
                <div class="team-card-desc">{{ Str::limit($team->description, 80) }}</div>
                @endif

                <div class="team-card-lead">
                    <div class="team-lead-avatar">
                        {{ $team->teamLead ? strtoupper(substr($team->teamLead->name, 0, 1)) : '?' }}
                    </div>
                    <div>
                        <div class="team-lead-name">{{ $team->teamLead?->name ?? 'No lead assigned' }}</div>
                        <div class="team-lead-label">Team Lead</div>
                    </div>
                </div>

                <div class="team-card-stats">
                    <div class="team-stat">
                        <div class="team-stat-val">{{ $memberCount }}/10</div>
                        <div class="team-stat-lbl">Members</div>
                    </div>
                    <div class="team-stat">
                        <div class="team-stat-val {{ $loggedToday === $memberCount && $memberCount > 0 ? 'text-green' : '' }}">
                            {{ $loggedToday }}/{{ $memberCount }}
                        </div>
                        <div class="team-stat-lbl">Logged Today</div>
                    </div>
                    <div class="team-stat">
                        <div class="team-stat-val">{{ $activeTasks }}</div>
                        <div class="team-stat-lbl">Active Tasks</div>
                    </div>
                </div>

                <div class="team-capacity-wrap">
                    <div class="team-capacity-bar">
                        <div class="team-capacity-fill {{ $isAtCapacity ? 'capacity-full' : ($capacityPct >= 70 ? 'capacity-high' : 'capacity-normal') }}"
                            style="width:{{ $capacityPct }}%"></div>
                    </div>
                    <span class="team-capacity-text">{{ $memberCount }}/10 capacity</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endforeach

    @endif

</div>
@endsection
