@extends('layouts.app')
@section('title', 'Departments')

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Departments</h1>
        <p class="page-subtitle">Manage teams and track work across your entire organization</p>
    </div>
    @can('manage_organization')
    <div class="page-header-right">
        <a href="{{ route('departments.create') }}" class="btn-primary">+ Add Department</a>
    </div>
    @endcan
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="fv-alert fv-alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="fv-alert fv-alert-warning" style="margin-bottom:20px;">{{ $errors->first() }}</div>
@endif

{{-- Department grid --}}
@if($departments->isEmpty())
<div class="fv-card" style="padding:60px 24px;text-align:center;color:#71717a;">
    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 16px;display:block;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
    </svg>
    <div style="font-size:1rem;font-weight:600;color:#374151;margin-bottom:8px;">No departments yet</div>
    <div style="font-size:0.875rem;margin-bottom:20px;">Create departments to organize your teams and enable work tracking for any role.</div>
    @can('manage_organization')
    <a href="{{ route('departments.create') }}" class="fv-btn fv-btn-primary">Create First Department</a>
    @endcan
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;margin-bottom:32px;">
    @foreach($departments as $item)
    @php $dept = $item['dept']; $todayLogs = $item['today_logs']; @endphp
    <div class="fv-card" style="padding:0;overflow:hidden;border-left:4px solid {{ $dept->color }};">
        <div style="padding:20px 20px 16px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                <div>
                    <div style="font-size:1rem;font-weight:800;color:#0f172a;margin-bottom:4px;">{{ $dept->name }}</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px;background:#f4f4f5;color:#52525b;">
                            {{ $dept->getTypeLabel() }}
                        </span>
                        @php
                            $modeBg    = match($dept->work_mode) { 'github' => '#18181b', 'hybrid' => '#eff6ff', default => '#f4f4f5' };
                            $modeColor = match($dept->work_mode) { 'github' => '#ffffff', 'hybrid' => '#1d4ed8', default => '#52525b' };
                            $modeLabel = match($dept->work_mode) { 'github' => 'GitHub', 'hybrid' => 'Hybrid', default => 'Manual' };
                        @endphp
                        <span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px;background:{{ $modeBg }};color:{{ $modeColor }};">
                            {{ $modeLabel }}
                        </span>
                    </div>
                </div>
                <div style="width:36px;height:36px;border-radius:9px;background:{{ $dept->color }}20;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
                    🏢
                </div>
            </div>

            @if($dept->description)
            <div style="font-size:0.8rem;color:#64748b;margin-bottom:12px;line-height:1.5;">{{ Str::limit($dept->description, 80) }}</div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div style="padding:10px 12px;background:#f8fafc;border-radius:8px;">
                    <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:3px;">Members</div>
                    <div style="font-size:1.25rem;font-weight:800;color:#0f172a;">{{ $dept->users_count }}</div>
                </div>
                <div style="padding:10px 12px;background:#f8fafc;border-radius:8px;">
                    <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:3px;">Today's Logs</div>
                    <div style="font-size:1.25rem;font-weight:800;color:{{ $todayLogs > 0 ? '#059669' : '#94a3b8' }};">{{ $todayLogs }}</div>
                </div>
            </div>

            @if($dept->head)
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <div style="width:24px;height:24px;border-radius:50%;background:#f4f4f5;border:1px solid #e4e4e7;display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;color:#3f3f46;flex-shrink:0;">
                    {{ strtoupper(substr($dept->head->name, 0, 2)) }}
                </div>
                <div style="font-size:0.78rem;color:#64748b;">Head: <span style="font-weight:700;color:#0f172a;">{{ $dept->head->name }}</span></div>
            </div>
            @else
            <div style="font-size:0.78rem;color:#94a3b8;margin-bottom:14px;">No department head assigned</div>
            @endif
        </div>

        <div style="padding:12px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
            @if($dept->users_count === 0)
            <span style="font-size:0.75rem;color:#94a3b8;">No members yet</span>
            @else
            <span style="font-size:0.75rem;color:#64748b;">{{ $dept->users_count }} {{ $dept->users_count === 1 ? 'member' : 'members' }}</span>
            @endif
            <a href="{{ route('departments.show', $dept->id) }}" class="fv-btn fv-btn-secondary" style="font-size:0.8rem;padding:6px 14px;">
                View →
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Work Mode Legend --}}
<div class="fv-card" style="padding:20px;">
    <div style="font-size:0.8rem;font-weight:800;color:#0f172a;margin-bottom:12px;">Work Mode Reference</div>
    <div style="display:flex;flex-wrap:wrap;gap:20px;">
        <div class="flex-center-gap-sm">
            <span style="width:10px;height:10px;border-radius:50%;background:#18181b;display:inline-block;flex-shrink:0;"></span>
            <span style="font-size:0.8rem;color:#475569;"><strong>GitHub Mode</strong> — Tracks commits and PRs automatically</span>
        </div>
        <div class="flex-center-gap-sm">
            <span style="width:10px;height:10px;border-radius:50%;background:#94a3b8;display:inline-block;flex-shrink:0;"></span>
            <span style="font-size:0.8rem;color:#475569;"><strong>Manual Mode</strong> — Daily work log entries by team members</span>
        </div>
        <div class="flex-center-gap-sm">
            <span style="width:10px;height:10px;border-radius:50%;background:#1d4ed8;display:inline-block;flex-shrink:0;"></span>
            <span style="font-size:0.8rem;color:#475569;"><strong>Hybrid Mode</strong> — Both GitHub tracking and daily work logs</span>
        </div>
    </div>
</div>

</div>
@endsection
