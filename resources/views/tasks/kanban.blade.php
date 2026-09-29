@extends('layouts.app')
@section('title', 'Board')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components.css') }}">
@endpush
@section('content')
<div style="padding:24px 24px 0;max-width:100%;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:1.4rem;font-weight:800;color:#09090b;margin:0 0 2px;">Board</h1>
            <p style="font-size:0.8rem;color:#71717a;margin:0;">Drag cards to change status</p>
        </div>
        <div style="display:flex;gap:8px;">
            <div style="display:flex;border:1px solid #e4e4e7;border-radius:8px;overflow:hidden;">
                <a href="?view=list" style="padding:6px 12px;font-size:0.78rem;font-weight:600;text-decoration:none;background:white;color:#71717a;">☰ List</a>
                <a href="?view=kanban" style="padding:6px 12px;font-size:0.78rem;font-weight:600;text-decoration:none;background:#09090b;color:white;">⬛ Board</a>
            </div>
            <a href="{{ route('tasks.create') }}" style="padding:7px 16px;background:#09090b;color:white;border-radius:8px;font-size:0.8rem;font-weight:700;text-decoration:none;">+ New Task</a>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;padding:0 24px 32px;overflow-x:auto;min-height:calc(100vh - 160px);">

@php
$columnDefs = [
    'backlog'     => ['label'=>'Backlog',     'color'=>'#a1a1aa'],
    'todo'        => ['label'=>'To Do',       'color'=>'#71717a'],
    'in_progress' => ['label'=>'In Progress', 'color'=>'#3b82f6'],
    'in_review'   => ['label'=>'In Review',   'color'=>'#8b5cf6'],
    'blocked'     => ['label'=>'Blocked',     'color'=>'#ef4444'],
    'done'        => ['label'=>'Done',        'color'=>'#10b981'],
];
@endphp

@foreach($columnDefs as $status => $col)
<div style="flex:0 0 270px;display:flex;flex-direction:column;gap:0;">
    {{-- Column Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:white;border:1px solid #e4e4e7;border-radius:10px 10px 0 0;border-bottom:2px solid {{ $col['color'] }};">
        <div class="flex-center-gap-sm">
            <span style="font-size:0.78rem;font-weight:800;color:#09090b;text-transform:uppercase;letter-spacing:0.04em;">{{ $col['label'] }}</span>
            <span style="padding:1px 7px;background:#f4f4f5;border-radius:99px;font-size:0.7rem;font-weight:700;color:#71717a;">{{ count($grouped[$status] ?? []) }}</span>
        </div>
        <a href="{{ route('tasks.create', ['status'=>$status]) }}" style="width:22px;height:22px;background:#f4f4f5;border-radius:5px;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#71717a;font-size:0.9rem;font-weight:700;">+</a>
    </div>

    {{-- Cards --}}
    <div class="kanban-column" data-status="{{ $status }}"
         style="flex:1;background:#f9f9fb;border:1px solid #e4e4e7;border-top:none;border-radius:0 0 10px 10px;padding:8px;display:flex;flex-direction:column;gap:6px;min-height:300px;">
        @foreach($grouped[$status] ?? [] as $task)
        <div class="kanban-card" data-task-id="{{ $task->id }}"
             style="background:white;border:1px solid #e4e4e7;border-radius:8px;padding:12px;cursor:grab;transition:box-shadow 0.15s;"
             onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                <div style="display:flex;align-items:center;gap:5px;">
                    <span style="font-size:0.8rem;">{{ $task->getTypeIcon() }}</span>
                    <span style="font-size:0.65rem;font-family:monospace;color:#a1a1aa;font-weight:600;">{{ $task->ticket_number ?? '?' }}</span>
                </div>
                <span style="font-size:0.85rem;">{{ $task->getPriorityIcon() }}</span>
            </div>
            <a href="{{ route('tasks.show', $task->id) }}"
               style="font-size:0.8rem;font-weight:700;color:#09090b;text-decoration:none;display:block;line-height:1.35;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                {{ $task->title }}
            </a>
            @if($task->label)
            <span style="display:inline-block;padding:2px 7px;background:#f4f4f5;border-radius:99px;font-size:0.62rem;color:#71717a;font-weight:600;margin-bottom:6px;">{{ $task->label }}</span>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
                <div>
                    @if($task->assignee)
                    <div style="width:22px;height:22px;border-radius:50%;background:#f4f4f5;border:1.5px solid #e4e4e7;display:flex;align-items:center;justify-content:center;font-size:0.58rem;font-weight:800;color:#3f3f46;" title="{{ $task->assignee->name }}">
                        {{ strtoupper(substr($task->assignee->name,0,2)) }}
                    </div>
                    @endif
                </div>
                @if($task->due_date)
                <span style="font-size:0.65rem;font-weight:600;color:{{ $task->isOverdue() ? '#dc2626' : '#a1a1aa' }};">
                    {{ $task->isOverdue() ? '⚠ ' : '' }}{{ $task->due_date->format('M j') }}
                </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>window.KANBAN_CSRF='{{ csrf_token() }}';</script>
<script src="{{ asset('js/kanban.js') }}"></script>
@endpush

@endsection
