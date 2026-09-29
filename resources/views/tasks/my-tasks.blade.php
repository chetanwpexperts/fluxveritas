@extends('layouts.app')
@section('title', 'My Tasks')
@section('content')
<div style="max-width:900px;margin:0 auto;padding:28px 24px;">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.4rem;font-weight:800;color:#09090b;margin:0 0 4px;">My Tasks</h1>
            <p style="font-size:0.8rem;color:#71717a;margin:0;">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('tasks.create') }}" style="padding:7px 16px;background:#09090b;color:white;border-radius:8px;font-size:0.8rem;font-weight:700;text-decoration:none;">+ New Task</a>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:24px;">
        @foreach([
            ['Done This Week', $stats['done_week'], '#10b981'],
            ['In Progress',    $stats['in_progress'], '#3b82f6'],
            ['Overdue',        $stats['overdue'], '#ef4444'],
            ['Total Assigned', $stats['total'], '#09090b'],
        ] as [$lbl,$val,$color])
        <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;padding:14px 16px;">
            <div style="font-size:1.5rem;font-weight:800;color:{{ $color }};">{{ $val }}</div>
            <div style="font-size:0.72rem;color:#71717a;font-weight:600;margin-top:2px;">{{ $lbl }}</div>
        </div>
        @endforeach
    </div>

    {{-- Today's Focus --}}
    @if($today->count() > 0)
    <div class="mb-lg">
        <div style="font-size:0.75rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Today's Focus</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($today as $task)
            <div style="background:white;border:1px solid {{ $task->isOverdue() ? '#fecaca' : '#e4e4e7' }};border-left:3px solid {{ $task->getStatusColor() }};border-radius:8px;padding:12px 14px;display:flex;align-items:center;gap:12px;">
                <span style="font-size:1rem;flex-shrink:0;">{{ $task->getPriorityIcon() }}</span>
                <div style="flex:1;min-width:0;">
                    <a href="{{ route('tasks.show',$task->id) }}" style="font-size:0.85rem;font-weight:700;color:#09090b;text-decoration:none;">{{ $task->title }}</a>
                    <div style="font-size:0.72rem;color:#a1a1aa;margin-top:2px;">{{ $task->project?->name }} @if($task->due_date) · Due {{ $task->due_date->format('M j') }}@endif</div>
                </div>
                <span style="padding:3px 8px;border-radius:99px;font-size:0.68rem;font-weight:700;color:white;background:{{ $task->getStatusColor() }};flex-shrink:0;">{{ $task->getStatusLabel() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- All My Tasks by Status --}}
    @php
    $sections = [
        'in_progress' => ['In Progress', '#3b82f6'],
        'todo'        => ['To Do', '#71717a'],
        'in_review'   => ['In Review', '#8b5cf6'],
        'blocked'     => ['Blocked', '#ef4444'],
        'backlog'     => ['Backlog', '#a1a1aa'],
        'done'        => ['Done', '#10b981'],
    ];
    @endphp

    @foreach($sections as $status => [$label, $color])
    @php $sectionTasks = $grouped[$status] ?? collect(); @endphp
    @if($sectionTasks->count() > 0)
    <div style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <span style="width:8px;height:8px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></span>
            <span style="font-size:0.75rem;font-weight:700;color:#3f3f46;text-transform:uppercase;letter-spacing:0.04em;">{{ $label }}</span>
            <span style="padding:1px 7px;background:#f4f4f5;border-radius:99px;font-size:0.7rem;font-weight:700;color:#71717a;">{{ $sectionTasks->count() }}</span>
        </div>
        <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
            @foreach($sectionTasks as $task)
            <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #fafafa;transition:background 0.1s;"
                 onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='white'">
                <span style="font-size:0.85rem;flex-shrink:0;">{{ $task->getPriorityIcon() }}</span>
                <span style="font-size:0.85rem;flex-shrink:0;">{{ $task->getTypeIcon() }}</span>
                <span style="font-size:0.65rem;font-family:monospace;color:#a1a1aa;font-weight:600;flex-shrink:0;min-width:60px;">{{ $task->ticket_number ?? '—' }}</span>
                <a href="{{ route('tasks.show',$task->id) }}" style="flex:1;font-size:0.82rem;font-weight:600;color:#09090b;text-decoration:none;">{{ $task->title }}</a>
                <span style="font-size:0.72rem;color:#a1a1aa;flex-shrink:0;">{{ $task->project?->name }}</span>
                @if($task->due_date)
                <span style="font-size:0.72rem;font-weight:600;color:{{ $task->isOverdue() ? '#dc2626' : '#a1a1aa' }};flex-shrink:0;">{{ $task->isOverdue() ? '⚠ ' : '' }}{{ $task->due_date->format('M j') }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach

    @if($tasks->isEmpty())
    <div style="text-align:center;padding:64px 24px;background:white;border:1px solid #e4e4e7;border-radius:12px;">
        <div style="font-size:2.5rem;margin-bottom:12px;">🎉</div>
        <div style="font-size:1rem;font-weight:700;color:#09090b;margin-bottom:6px;">No tasks assigned to you</div>
        <div style="font-size:0.82rem;color:#71717a;">Enjoy the free time or create a new task!</div>
    </div>
    @endif
</div>
@endsection
