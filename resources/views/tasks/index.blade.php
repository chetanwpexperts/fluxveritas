@extends('layouts.app')
@section('title', 'Tasks')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tasks.css') }}">
@endpush
@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">
            @if($isAdminView ?? false)
                @if(auth()->user()->hasRole('team_lead'))
                    Team Tasks
                @else
                    All Tasks
                @endif
            @else
                My Tasks
            @endif
        </h1>
        <p class="page-subtitle">
            @if($isAdminView ?? false)
                @if(auth()->user()->hasRole('team_lead'))
                    Tasks across your direct team
                @else
                    All tasks across your organization
                @endif
            @else
                {{ now()->format('l, F j, Y') }}
            @endif
        </p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('tasks.create') }}" class="btn-primary">+ New Task</a>
    </div>
</div>

{{-- Stats --}}
<div class="tk-stats-grid">
    <div class="tk-stat-card">
        <div class="tk-stat-value" style="color:#09090b;">{{ $stats['total'] }}</div>
        <div class="tk-stat-label">{{ ($isAdminView ?? false) ? 'Total Tasks' : 'Total Assigned' }}</div>
    </div>
    <div class="tk-stat-card">
        <div class="tk-stat-value" style="color:#3b82f6;">{{ $stats['in_progress'] }}</div>
        <div class="tk-stat-label">In Progress</div>
    </div>
    <div class="tk-stat-card">
        <div class="tk-stat-value" style="color:#10b981;">{{ $stats['done_week'] }}</div>
        <div class="tk-stat-label">Done This Week</div>
    </div>
    <div class="tk-stat-card">
        <div class="tk-stat-value" style="color:#ef4444;">{{ $stats['overdue'] }}</div>
        <div class="tk-stat-label">Overdue</div>
    </div>
    @if(($isAdminView ?? false) && ($stats['unassigned'] ?? 0) > 0)
    <div class="tk-stat-card task-stat-warn">
        <div class="tk-stat-value">{{ $stats['unassigned'] }}</div>
        <div class="tk-stat-label">Unassigned</div>
    </div>
    @endif
</div>

{{-- Filters (admin / team_lead view) --}}
@if($isAdminView ?? false)
<div class="task-filters-row">
    <form method="GET" action="{{ route('tasks.index') }}" class="task-filters-form">

        <select name="status" class="task-filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending"     {{ request('status') === 'pending'      ? 'selected' : '' }}>Pending</option>
            <option value="in_progress" {{ request('status') === 'in_progress'  ? 'selected' : '' }}>In Progress</option>
            <option value="review"      {{ request('status') === 'review'       ? 'selected' : '' }}>In Review</option>
            <option value="done"        {{ request('status') === 'done'         ? 'selected' : '' }}>Done</option>
            <option value="cancelled"   {{ request('status') === 'cancelled'    ? 'selected' : '' }}>Cancelled</option>
        </select>

        @if($teamMembers->count() > 0)
        <select name="assignee" class="task-filter-select" onchange="this.form.submit()">
            <option value="">All Members</option>
            @foreach($teamMembers as $member)
            <option value="{{ $member->id }}" {{ request('assignee') == $member->id ? 'selected' : '' }}>
                {{ $member->name }}
            </option>
            @endforeach
        </select>
        @endif

        <select name="priority" class="task-filter-select" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            <option value="urgent"  {{ request('priority') === 'urgent'  ? 'selected' : '' }}>🔴 Urgent</option>
            <option value="high"    {{ request('priority') === 'high'    ? 'selected' : '' }}>🟠 High</option>
            <option value="medium"  {{ request('priority') === 'medium'  ? 'selected' : '' }}>🟡 Medium</option>
            <option value="low"     {{ request('priority') === 'low'     ? 'selected' : '' }}>🟢 Low</option>
        </select>

        @if(request()->hasAny(['status', 'assignee', 'priority']))
        <a href="{{ route('tasks.index') }}" class="task-filter-clear">Clear filters ✕</a>
        @endif

    </form>
</div>
@endif

{{-- Task Table --}}
@if($tasks->isEmpty())
<div class="tk-empty">
    <svg class="tk-empty-icon" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#d1d5db" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
    </svg>
    <div class="tk-empty-title">No tasks yet</div>
    <div class="tk-empty-desc">
        @if(request()->hasAny(['status', 'assignee', 'priority']))
        No tasks match your current filters. Try clearing the filters.
        @else
        Create your first task to start tracking work.
        @endif
    </div>
    @if(request()->hasAny(['status', 'assignee', 'priority']))
    <a href="{{ route('tasks.index') }}" class="tk-btn-outline">Clear Filters</a>
    @else
    <a href="{{ route('tasks.create') }}" class="tk-btn-dark">+ New Task</a>
    @endif
</div>
@else
<div class="tk-table-wrap">
    <table class="tk-table">
        <thead>
            <tr>
                <th class="tk-th-type">TYPE</th>
                <th class="tk-th-ticket">TICKET</th>
                <th>TITLE</th>
                <th class="tk-th-assign">ASSIGNEE</th>
                <th class="tk-th-status">STATUS</th>
                <th class="tk-th-pri tk-th-center">PRI</th>
                <th class="tk-th-due">DUE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
            <tr>
                <td class="tk-td-type">{{ $task->getTypeIcon() }}</td>
                <td>
                    <span class="tk-ticket-num">{{ $task->ticket_number ?? '—' }}</span>
                </td>
                <td>
                    <a href="{{ route('tasks.show', $task->id) }}" class="tk-task-link">{{ $task->title }}</a>
                    @if($task->subtasks->count() > 0)
                    <span class="tk-subtask-count">↳ {{ $task->subtasks->count() }} sub</span>
                    @endif
                    @if($task->label)
                    <span class="tk-label-badge">{{ $task->label }}</span>
                    @endif
                </td>
                <td>
                    @if($task->assignee)
                    <div class="tk-assignee-wrap">
                        <div class="tk-assignee-avatar">{{ strtoupper(substr($task->assignee->name, 0, 2)) }}</div>
                        <span class="tk-assignee-name">{{ explode(' ', $task->assignee->name)[0] }}</span>
                    </div>
                    @else
                    <span class="tk-no-value">—</span>
                    @endif
                </td>
                <td>
                    <span class="tk-status-badge" style="background:{{ $task->getStatusColor() }};">
                        {{ $task->getStatusLabel() }}
                    </span>
                </td>
                <td class="tk-td-center">{{ $task->getPriorityIcon() }}</td>
                <td>
                    @if($task->due_date)
                    <span class="tk-due-date {{ $task->isOverdue() ? 'tk-due-overdue' : '' }}">
                        {{ $task->isOverdue() ? '⚠ ' : '' }}{{ $task->due_date->format('M j') }}
                    </span>
                    @else
                    <span class="tk-no-value">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($tasks->hasPages())
<div style="margin-top:16px;">{{ $tasks->appends(request()->query())->links() }}</div>
@endif

@endif

</div>
@endsection
