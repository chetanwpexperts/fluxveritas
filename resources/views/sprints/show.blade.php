@extends('layouts.app')
@section('title', $sprint->name)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/sprints.css') }}">
@endpush
@section('content')

@php
    $statusBadgeBg   = ['active' => '#dcfce7', 'planning' => '#dbeafe', 'completed' => '#f4f4f5'];
    $statusBadgeTxt  = ['active' => '#16a34a', 'planning' => '#1d4ed8', 'completed' => '#71717a'];

    $sBg  = $statusBadgeBg[$sprint->status]  ?? '#f4f4f5';
    $sTxt = $statusBadgeTxt[$sprint->status] ?? '#71717a';

    // Sprint duration / elapsed
    $totalDays   = $sprint->start_date && $sprint->end_date ? $sprint->start_date->diffInDays($sprint->end_date) : 0;
    $elapsedDays = $sprint->start_date ? min($sprint->start_date->diffInDays(now()), $totalDays) : 0;
    $daysPct     = $totalDays > 0 ? round(($elapsedDays / $totalDays) * 100) : 0;

    // Task stats
    $tasks       = $sprint->tasks;
    $totalTasks  = $tasks->count();
    $doneTasks   = $tasks->where('status', 'done')->count();
    $inProg      = $tasks->where('status', 'in_progress')->count();
    $blocked     = $tasks->where('status', 'blocked')->count();
    $compPct     = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

    $incompleteCount = $tasks->where('status', '!=', 'done')->count();

    // Status colours for task badges
    $taskStatusBg  = [
        'done'        => '#dcfce7', 'in_progress' => '#dbeafe', 'blocked'   => '#fee2e2',
        'in_review'   => '#ede9fe', 'todo'        => '#f4f4f5', 'backlog'   => '#f4f4f5',
        'cancelled'   => '#f4f4f5',
    ];
    $taskStatusTxt = [
        'done'        => '#10b981', 'in_progress' => '#3b82f6', 'blocked'   => '#ef4444',
        'in_review'   => '#8b5cf6', 'todo'        => '#6b7280', 'backlog'   => '#a1a1aa',
        'cancelled'   => '#a1a1aa',
    ];
    $priorityIcon = ['critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
    $typeIcon     = ['task' => '📋', 'bug' => '🐛', 'feature' => '✨', 'improvement' => '📈', 'story' => '📖', 'epic' => '⚡'];
@endphp

<div class="spr-page">

    {{-- BREADCRUMB --}}
    <div class="spr-breadcrumb">
        <a href="{{ route('sprints.index') }}" class="spr-breadcrumb-link">← Sprints</a>
        <span class="spr-breadcrumb-sep">›</span>
        <span class="spr-breadcrumb-current">{{ $sprint->name }}</span>
    </div>

    {{-- SUCCESS FLASH --}}
    @if(session('success'))
    <div class="spr-flash-success">{{ session('success') }}</div>
    @endif

    {{-- HEADER CARD --}}
    <div class="spr-header-card">
        <div class="spr-header-row">
            <h1 class="spr-title">{{ $sprint->name }}</h1>
            <span class="spr-status-badge" style="background:{{ $sBg }};color:{{ $sTxt }};">{{ ucfirst($sprint->status) }}</span>
        </div>

        <div class="spr-project-row">
            <a href="{{ route('projects.show', $sprint->project->id) }}" class="spr-project-link">{{ $sprint->project->name }}</a>
        </div>

        @if($sprint->goal)
        <div class="spr-goal">{{ $sprint->goal }}</div>
        @endif

        <div>
            <div class="spr-date-text">
                {{ $sprint->start_date ? $sprint->start_date->format('M j') : '—' }}
                →
                {{ $sprint->end_date ? $sprint->end_date->format('M j, Y') : '—' }}
                @if($totalDays > 0)
                <span class="spr-elapsed">· {{ $elapsedDays }} of {{ $totalDays }} days elapsed</span>
                @endif
            </div>
            @if($totalDays > 0)
            <div class="spr-day-bar">
                <div class="spr-day-fill" style="width:{{ $daysPct }}%;"></div>
            </div>
            @endif
        </div>
    </div>

    {{-- STATS ROW --}}
    <div class="spr-stats-grid">
        <div class="spr-stat-card">
            <div class="spr-stat-value" style="color:#09090b;">{{ $totalTasks }}</div>
            <div class="spr-stat-label">Total Tasks</div>
        </div>
        <div class="spr-stat-card">
            <div class="spr-stat-value" style="color:#10b981;">{{ $doneTasks }}</div>
            <div class="spr-stat-label">Completed</div>
        </div>
        <div class="spr-stat-card">
            <div class="spr-stat-value" style="color:#3b82f6;">{{ $inProg }}</div>
            <div class="spr-stat-label">In Progress</div>
        </div>
        <div class="spr-stat-card">
            <div class="spr-stat-value" style="color:#ef4444;">{{ $blocked }}</div>
            <div class="spr-stat-label">Blocked</div>
        </div>
    </div>

    {{-- MAIN 2-COLUMN LAYOUT --}}
    <div class="spr-layout">

        {{-- LEFT COLUMN: Sprint Tasks --}}
        <div>
            <div class="spr-tasks-card">

                <div class="spr-tasks-header">
                    <div class="spr-tasks-title">Sprint Tasks ({{ $totalTasks }})</div>
                    <button type="button" class="spr-add-btn"
                        onclick="document.getElementById('add-task-panel').style.display = document.getElementById('add-task-panel').style.display === 'none' ? 'block' : 'none';">
                        + Add Task
                    </button>
                </div>

                {{-- ADD TASK PANEL --}}
                <div id="add-task-panel" class="spr-add-panel" style="display:none;">
                    <div class="spr-panel-label">Add Task to Sprint</div>
                    <form method="POST" action="{{ route('sprints.add-task', $sprint->id) }}">
                        @csrf
                        <div class="spr-panel-row">
                            <select name="task_id" required class="spr-panel-select">
                                <option value="">Select a task…</option>
                                @foreach($availableTasks as $t)
                                <option value="{{ $t->id }}">{{ $t->ticket_number ?? '#' . $t->id }} — {{ Str::limit($t->title, 60) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="spr-panel-submit">Add</button>
                        </div>
                    </form>
                    @if($availableTasks->isEmpty())
                    <div class="spr-panel-empty">No available tasks to add.</div>
                    @endif
                </div>

                {{-- TASKS TABLE --}}
                @if($tasks->isNotEmpty())
                <div class="spr-table-wrap">
                    <table class="spr-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Ticket</th>
                                <th>Title</th>
                                <th>Assignee</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th class="spr-th-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                            @php
                                $tBg   = $taskStatusBg[$task->status]  ?? '#f4f4f5';
                                $tTxt  = $taskStatusTxt[$task->status] ?? '#71717a';
                                $tIcon = $typeIcon[$task->type]         ?? '📋';
                                $pIcon = $priorityIcon[$task->priority] ?? '🟡';
                            @endphp
                            <tr>
                                <td class="spr-td-icon">{{ $tIcon }}</td>
                                <td class="spr-td-ticket">{{ $task->ticket_number ?? '#' . $task->id }}</td>
                                <td class="spr-td-title">
                                    <a href="{{ route('tasks.show', $task->id) }}" class="spr-task-link">{{ Str::limit($task->title, 55) }}</a>
                                </td>
                                <td class="spr-td-assignee">{{ $task->assignee?->name ?? '—' }}</td>
                                <td class="spr-td-status">
                                    <span class="spr-status-pill" style="background:{{ $tBg }};color:{{ $tTxt }};">{{ ucwords(str_replace('_', ' ', $task->status)) }}</span>
                                </td>
                                <td class="spr-td-center">{{ $pIcon }}</td>
                                <td class="spr-td-center">
                                    <form method="POST" action="{{ route('sprints.remove-task', [$sprint->id, $task->id]) }}"
                                          class="spr-remove-form"
                                          onsubmit="return confirm('Remove this task from the sprint?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="spr-remove-btn" title="Remove from sprint">✕</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="spr-tasks-empty">
                    No tasks in this sprint yet. Add tasks using the button above.
                </div>
                @endif

                {{-- COMPLETION PROGRESS --}}
                <div class="spr-progress-wrap">
                    <div class="spr-progress-header">
                        <span>Sprint Progress</span>
                        <span class="spr-progress-pct">{{ $compPct }}%</span>
                    </div>
                    <div class="spr-progress-bar">
                        <div class="spr-progress-fill" style="width:{{ $compPct }}%;"></div>
                    </div>
                </div>

            </div>
        </div>

        {{-- RIGHT SIDEBAR --}}
        <div class="spr-sidebar">

            {{-- SPRINT ACTIONS CARD --}}
            <div class="spr-sidebar-card">
                <div class="spr-sidebar-title">Sprint Actions</div>

                @if($sprint->status === 'planning')
                <form method="POST" action="{{ route('sprints.start', $sprint->id) }}">
                    @csrf
                    <button type="submit" class="spr-action-btn spr-action-btn-start">▶ Start Sprint</button>
                </form>
                <p class="spr-action-hint">Starting will make this the active sprint for the project.</p>

                @elseif($sprint->status === 'active')
                <form method="POST" action="{{ route('sprints.complete', $sprint->id) }}"
                      onsubmit="return confirm('Complete sprint? {{ $incompleteCount }} task(s) will move to backlog.')">
                    @csrf
                    <button type="submit" class="spr-action-btn spr-action-btn-complete">✓ Complete Sprint</button>
                </form>
                @if($incompleteCount > 0)
                <p class="spr-action-warn">⚠ {{ $incompleteCount }} incomplete task(s) will move to backlog</p>
                @endif

                @elseif($sprint->status === 'completed')
                <div class="spr-done-state">
                    <div class="spr-done-icon">✅</div>
                    <div class="spr-done-title">Sprint Complete</div>
                    <div class="spr-done-date">Completed {{ $sprint->completed_at?->format('M j, Y') }}</div>
                </div>
                @endif
            </div>

            {{-- SPRINT INFO CARD --}}
            <div class="spr-sidebar-card">
                <div class="spr-sidebar-title">Sprint Info</div>
                <div class="spr-info-rows">
                    <div class="spr-info-row">
                        <span class="spr-info-label">Status</span>
                        <span class="spr-status-pill" style="background:{{ $sBg }};color:{{ $sTxt }};">{{ ucfirst($sprint->status) }}</span>
                    </div>
                    <div class="spr-info-row">
                        <span class="spr-info-label">Project</span>
                        <a href="{{ route('projects.show', $sprint->project->id) }}" class="spr-info-link">{{ Str::limit($sprint->project->name, 24) }}</a>
                    </div>
                    <div class="spr-info-row">
                        <span class="spr-info-label">Start Date</span>
                        <span class="spr-info-value">{{ $sprint->start_date ? $sprint->start_date->format('M j, Y') : '—' }}</span>
                    </div>
                    <div class="spr-info-row">
                        <span class="spr-info-label">End Date</span>
                        <span class="spr-info-value">{{ $sprint->end_date ? $sprint->end_date->format('M j, Y') : '—' }}</span>
                    </div>
                    @if($sprint->relationLoaded('createdBy') && $sprint->createdBy)
                    <div class="spr-info-row">
                        <span class="spr-info-label">Created by</span>
                        <span class="spr-info-value">{{ $sprint->createdBy->name }}</span>
                    </div>
                    @endif
                    <div class="spr-info-row">
                        <span class="spr-info-label">Tasks</span>
                        <span class="spr-info-value">{{ $totalTasks }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
