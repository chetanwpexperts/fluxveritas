@extends('layouts.app')
@section('title', 'Manager Dashboard')
@section('content')

<div class="page-wrapper">

  {{-- Greeting --}}
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title">
        Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
        {{ $user->name }}
      </h1>
      <p class="page-subtitle">
        {{ now()->format('l, F j, Y') }} · Team Manager View
      </p>
    </div>
  </div>

  {{-- Stat cards --}}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:1.5rem" class="anim-fade-up">

    <div class="fv-card" style="padding:1.25rem">
      <div style="font-size:2rem;font-weight:700;color:var(--text);line-height:1">{{ $teamMembers->count() }}</div>
      <div style="font-size:0.78rem;color:var(--text-3);margin-top:6px">Direct Reports</div>
    </div>

    <div class="fv-card" style="padding:1.25rem;{{ $pendingLeaves->count() > 0 ? 'border-color:#f59e0b' : '' }}">
      <div style="font-size:2rem;font-weight:700;color:{{ $pendingLeaves->count() > 0 ? '#b45309' : 'var(--text)' }};line-height:1">
        {{ $pendingLeaves->count() }}
      </div>
      <div style="font-size:0.78rem;color:var(--text-3);margin-top:6px">Pending Leaves</div>
    </div>

    <div class="fv-card" style="padding:1.25rem;{{ $notLoggedToday->count() > 0 ? 'border-color:#f87171' : '' }}">
      <div style="font-size:2rem;font-weight:700;color:{{ $notLoggedToday->count() > 0 ? '#dc2626' : 'var(--text)' }};line-height:1">
        {{ $notLoggedToday->count() }}
      </div>
      <div style="font-size:0.78rem;color:var(--text-3);margin-top:6px">Not Logged Today</div>
    </div>

    <div class="fv-card" style="padding:1.25rem;{{ $activeBlockers > 0 ? 'border-color:#f87171' : '' }}">
      <div style="font-size:2rem;font-weight:700;color:{{ $activeBlockers > 0 ? '#dc2626' : 'var(--text)' }};line-height:1">
        {{ $activeBlockers }}
      </div>
      <div style="font-size:0.78rem;color:var(--text-3);margin-top:6px">Active Blockers</div>
    </div>

  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:1.5rem" class="anim-fade-up delay-1">

    {{-- Pending leave requests --}}
    <div class="fv-card" style="padding:0">
      <div class="fv-section-header">
        <div>
          <div class="fv-section-title">Pending Leave Requests</div>
          <div class="fv-section-sub">Awaiting your approval</div>
        </div>
        @if($pendingLeaves->count() > 0)
        <a href="{{ route('leaves.index') }}" class="fv-btn fv-btn-secondary" style="font-size:0.78rem;padding:5px 12px">
          View All
        </a>
        @endif
      </div>

      @forelse($pendingLeaves as $leave)
      <div style="display:flex;justify-content:space-between;align-items:center;
                  padding:10px 20px;border-bottom:0.5px solid var(--border)">
        <div>
          <div style="font-size:0.85rem;font-weight:600;color:var(--text)">
            {{ $leave->user?->name ?? '—' }}
          </div>
          <div style="font-size:0.75rem;color:var(--text-3);margin-top:1px">
            {{ $leave->leaveType?->name ?? 'Leave' }}
            · {{ \Carbon\Carbon::parse($leave->from_date)->format('M j') }}
            @if($leave->from_date !== $leave->to_date)
              – {{ \Carbon\Carbon::parse($leave->to_date)->format('M j') }}
            @endif
          </div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <form method="POST" action="{{ route('leaves.approve', $leave->id) }}">
            @csrf
            <button type="submit"
                    style="font-size:0.75rem;padding:4px 10px;border-radius:6px;
                           border:0.5px solid #16a34a;color:#16a34a;background:transparent;
                           cursor:pointer;font-family:inherit"
                    onclick="return confirm('Approve leave for {{ $leave->user?->name }}?')">
              Approve
            </button>
          </form>
          <form method="POST" action="{{ route('leaves.reject', $leave->id) }}">
            @csrf
            <button type="submit"
                    style="font-size:0.75rem;padding:4px 10px;border-radius:6px;
                           border:0.5px solid #dc2626;color:#dc2626;background:transparent;
                           cursor:pointer;font-family:inherit"
                    onclick="return confirm('Reject leave for {{ $leave->user?->name }}?')">
              Reject
            </button>
          </form>
        </div>
      </div>
      @empty
      <div style="padding:2rem;text-align:center;color:var(--text-3);font-size:0.85rem">
        No pending requests
      </div>
      @endforelse
    </div>

    {{-- Team members --}}
    <div class="fv-card" style="padding:0">
      <div class="fv-section-header">
        <div>
          <div class="fv-section-title">My Team</div>
          <div class="fv-section-sub">Direct reports</div>
        </div>
        <a href="{{ route('directory.index') }}" class="fv-btn fv-btn-secondary" style="font-size:0.78rem;padding:5px 12px">
          Directory
        </a>
      </div>

      @forelse($teamMembers as $member)
      <div style="display:flex;align-items:center;gap:12px;
                  padding:10px 20px;border-bottom:0.5px solid var(--border)">
        <div style="width:34px;height:34px;border-radius:50%;
                    background:var(--text);color:white;
                    display:flex;align-items:center;justify-content:center;
                    font-size:0.7rem;font-weight:700;flex-shrink:0">
          {{ strtoupper(substr($member->name, 0, 2)) }}
        </div>
        <div style="min-width:0;flex:1">
          <div style="font-size:0.85rem;font-weight:600;color:var(--text);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ $member->name }}
          </div>
          <div style="font-size:0.72rem;color:var(--text-3)">
            {{ $member->department?->name ?? 'No department' }}
          </div>
        </div>
        @if($notLoggedToday->contains('id', $member->id))
        <span style="font-size:0.65rem;padding:2px 7px;border-radius:20px;
                     background:#fef2f2;color:#dc2626;border:0.5px solid #fecaca;
                     white-space:nowrap;flex-shrink:0">
          Not logged
        </span>
        @else
        <span style="font-size:0.65rem;padding:2px 7px;border-radius:20px;
                     background:#f0fdf4;color:#16a34a;border:0.5px solid #bbf7d0;
                     white-space:nowrap;flex-shrink:0">
          Logged
        </span>
        @endif
      </div>
      @empty
      <div style="padding:2rem;text-align:center;color:var(--text-3);font-size:0.85rem">
        No direct reports assigned yet.<br>
        <span style="font-size:0.75rem">Set reporting manager in employee profiles to see your team here.</span>
      </div>
      @endforelse
    </div>

  </div>

  {{-- Quick links --}}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px" class="anim-fade-up delay-2">
    @php
    $quickLinks = [
      ['label' => 'Teams',         'route' => 'teams.index',    'desc' => 'View all teams',       'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
      ['label' => 'All Leaves',    'route' => 'leaves.index',   'desc' => 'Manage team leaves',   'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
      ['label' => 'Team Reports',  'route' => 'reports.team',   'desc' => 'Performance overview', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
      ['label' => 'Team Feedback', 'route' => 'feedback.index', 'desc' => 'Give team feedback',  'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z'],
    ];
    @endphp
    @foreach($quickLinks as $link)
    @if(\Illuminate\Support\Facades\Route::has($link['route']))
    <a href="{{ route($link['route']) }}"
       class="fv-card"
       style="padding:1.25rem;text-decoration:none;display:flex;align-items:flex-start;gap:12px;">
      <div style="width:36px;height:36px;border-radius:10px;background:var(--surface);
                  border:0.5px solid var(--border);
                  display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg style="width:17px;height:17px;color:var(--text-2)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
          <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
        </svg>
      </div>
      <div>
        <div style="font-size:0.85rem;font-weight:600;color:var(--text)">{{ $link['label'] }}</div>
        <div style="font-size:0.72rem;color:var(--text-3);margin-top:2px">{{ $link['desc'] }}</div>
      </div>
    </a>
    @endif
    @endforeach
  </div>

</div>
@endsection
