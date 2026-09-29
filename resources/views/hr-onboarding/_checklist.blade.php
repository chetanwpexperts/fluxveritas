@php
    $total = $checklist->tasks->count();
    $done  = $checklist->tasks->where('is_completed', true)->count();
    $pct   = $total > 0 ? (int) round(($done / $total) * 100) : 0;
    $overdue = $checklist->due_date && $checklist->due_date->isPast() && is_null($checklist->completed_at);
@endphp

<div class="onb-checklist-card">
    <div class="onb-progress-header">
        <div class="onb-progress-meta">
            <span class="onb-progress-pct">{{ $pct }}%</span>
            <span class="onb-progress-count">{{ $done }}/{{ $total }} tasks complete</span>
        </div>
        @if($checklist->due_date)
            <span class="onb-due-label" style="color:{{ $overdue ? '#ef4444' : '#52525b' }};">
                Due {{ $checklist->due_date->format('M d, Y') }}
                @if($overdue) — <strong>Overdue</strong>@endif
            </span>
        @endif
    </div>

    <div class="onb-progress-bar-outer">
        <div class="onb-progress-bar-inner" style="width:{{ $pct }}%;background:{{ $pct === 100 ? '#10b981' : '#6366f1' }};"></div>
    </div>

    @if($checklist->completed_at)
        <div class="onb-complete-banner">Onboarding completed on {{ $checklist->completed_at->format('M d, Y') }}</div>
    @endif

    <ul class="onb-task-checklist">
        @foreach($checklist->tasks as $task)
        <li class="onb-task-item {{ $task->is_completed ? 'onb-task-done' : '' }}">
            <form method="POST" action="{{ route('hr-onboarding.tasks.complete', $task) }}" class="onb-task-check-form">
                @csrf
                <button type="submit" class="onb-task-check {{ $task->is_completed ? 'onb-task-check-done' : '' }}">
                    {{ $task->is_completed ? '✓' : '' }}
                </button>
            </form>
            <div class="onb-task-text">
                <span class="onb-task-title {{ $task->is_completed ? 'onb-task-title-done' : '' }}">{{ $task->title }}</span>
                @if($task->description)
                    <span class="onb-task-desc">{{ $task->description }}</span>
                @endif
                @if($task->assigned_to_user && $task->assignedTo)
                    <span class="onb-task-assigned">Assigned to {{ $task->assignedTo->name }}</span>
                @endif
                @if($task->due_date)
                    <span class="onb-task-due {{ $task->due_date->isPast() && !$task->is_completed ? 'onb-task-due-late' : '' }}">Due {{ $task->due_date->format('M d') }}</span>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
</div>
