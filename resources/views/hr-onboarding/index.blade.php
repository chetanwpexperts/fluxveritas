@extends('layouts.app')

@section('title', 'Onboarding Checklists')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hr-onboarding.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="onb-header">
        <div>
            <h1 class="onb-title">Onboarding Checklists</h1>
            <p class="onb-subtitle">Track employee onboarding progress</p>
        </div>
        <a href="{{ route('hr-onboarding.create') }}" class="onb-new-btn">+ New Checklist</a>
    </div>

    @if(session('success'))
        <div class="onb-alert">{{ session('success') }}</div>
    @endif

    @if($checklists->isEmpty())
        <div class="onb-empty">No onboarding checklists yet. Create one for a new employee.</div>
    @else
        <div class="onb-table-wrap">
            <table class="onb-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checklists as $cl)
                    @php
                        $total    = $cl->tasks->count();
                        $done     = $cl->tasks->where('is_completed', true)->count();
                        $pct      = $total > 0 ? (int) round(($done / $total) * 100) : 0;
                        $overdue  = $cl->due_date && $cl->due_date->isPast() && is_null($cl->completed_at);
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $cl->employee->name }}</div>
                            <div style="font-size:12px;color:#71717a;">{{ $cl->employee->job_title ?? $cl->employee->designation ?? '' }}</div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:6px;background:#e4e4e7;border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $pct }}%;background:{{ $pct === 100 ? '#10b981' : '#6366f1' }};border-radius:3px;"></div>
                                </div>
                                <span style="font-size:12px;color:#52525b;min-width:36px;">{{ $done }}/{{ $total }}</span>
                            </div>
                        </td>
                        <td style="color:{{ $overdue ? '#ef4444' : '#52525b' }};">
                            {{ $cl->due_date?->format('M d, Y') ?? '—' }}
                            @if($overdue)<span class="onb-overdue-badge">Overdue</span>@endif
                        </td>
                        <td>
                            @if($cl->completed_at)
                                <span class="onb-status-badge onb-status-done">Complete</span>
                            @elseif($overdue)
                                <span class="onb-status-badge onb-status-overdue">Overdue</span>
                            @else
                                <span class="onb-status-badge onb-status-active">In Progress</span>
                            @endif
                        </td>
                        <td style="font-size:13px;color:#71717a;">{{ $cl->created_at->format('M d, Y') }}</td>
                        <td><a href="{{ route('hr-onboarding.show', $cl) }}" class="onb-view-btn">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">{{ $checklists->links() }}</div>
    @endif

</div>
@endsection
