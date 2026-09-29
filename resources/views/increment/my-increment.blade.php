@extends('layouts.app')
@section('title', 'My Performance')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/performance.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">My Performance</h1>
            <p class="page-subtitle">
                {{ $designationLabel }} &middot;
                Target: {{ $seniorityBenchmark }}% for your seniority level
            </p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('worklog.index') }}" class="btn-primary">+ Log Work</a>
        </div>
    </div>

    {{-- ROW 1: TODAY'S LIVE STATS --}}
    <div class="perf-today-grid">
        <div class="perf-stat-card">
            <div class="perf-stat-icon">🕐</div>
            <div class="perf-stat-value">{{ $todayHours }}h</div>
            <div class="perf-stat-label">Hours Today</div>
            <div class="perf-stat-sub">{{ $todayLogs->count() }} log entries</div>
        </div>
        <div class="perf-stat-card">
            <div class="perf-stat-icon">✅</div>
            <div class="perf-stat-value perf-green">{{ $todayTasksDone }}</div>
            <div class="perf-stat-label">Tasks Done Today</div>
            <div class="perf-stat-sub">{{ $totalActiveTasks }} active tasks</div>
        </div>
        <div class="perf-stat-card">
            <div class="perf-stat-icon">⭐</div>
            <div class="perf-stat-value {{ $todayOutputAvg >= 7 ? 'perf-green' : ($todayOutputAvg >= 5 ? 'perf-amber' : ($todayOutputAvg > 0 ? 'perf-red' : '')) }}">
                {{ $todayOutputAvg > 0 ? $todayOutputAvg . '/10' : '—' }}
            </div>
            <div class="perf-stat-label">Output Quality</div>
            <div class="perf-stat-sub">Today's average</div>
        </div>
        <div class="perf-stat-card">
            <div class="perf-stat-icon">🔥</div>
            <div class="perf-stat-value perf-orange">{{ $streak }}</div>
            <div class="perf-stat-label">Day Streak</div>
            <div class="perf-stat-sub">Consecutive days logged</div>
        </div>
    </div>

    {{-- MOTIVATIONAL MESSAGE --}}
    <div class="perf-motivation-card {{ $motivationData['class'] }}">
        <span class="perf-motivation-emoji">{{ $motivationData['emoji'] }}</span>
        <span class="perf-motivation-text">{{ $motivationData['message'] }}</span>
    </div>

    {{-- ROW 2: THIS WEEK + LIVE SCORE --}}
    <div class="perf-section-grid">

        <div class="perf-card perf-card-wide">
            <h3 class="perf-card-title">This Week</h3>
            <div class="perf-week-chart" id="weekChart"
                data-weekdays="{{ json_encode($weekDays) }}">
            </div>
            <div class="perf-week-stats">
                <div class="perf-week-stat">
                    <span class="perf-week-stat-value">{{ $weekHours }}h</span>
                    <span class="perf-week-stat-label">Total Hours</span>
                </div>
                <div class="perf-week-stat">
                    <span class="perf-week-stat-value">{{ $weekConsistency }}%</span>
                    <span class="perf-week-stat-label">Consistency</span>
                </div>
                <div class="perf-week-stat">
                    <span class="perf-week-stat-value">{{ $weekTasksDone }}</span>
                    <span class="perf-week-stat-label">Tasks Done</span>
                </div>
                <div class="perf-week-stat">
                    <span class="perf-week-stat-value">{{ $bestDay }}</span>
                    <span class="perf-week-stat-label">Best Day</span>
                </div>
            </div>
        </div>

        <div class="perf-card">
            <h3 class="perf-card-title">Live Score &mdash; {{ now()->format('F') }}</h3>
            <div class="perf-score-circle-wrap">
                <div class="perf-score-circle {{ $monthScore >= $seniorityBenchmark ? 'score-green' : ($monthScore >= $seniorityBenchmark * 0.8 ? 'score-amber' : 'score-red') }}">
                    <span class="perf-score-number">{{ $monthScore }}</span>
                    <span class="perf-score-pct">%</span>
                </div>
                <p class="perf-score-label">Estimated Performance Score</p>
                <p class="perf-score-note">Updates in real-time as you work</p>
                <div class="perf-benchmark-note">
                    <span class="perf-benchmark-target">Target: {{ $seniorityBenchmark }}%</span>
                    <span class="perf-benchmark-status">
                        @if($monthScore >= $seniorityBenchmark)
                            ✓ Meeting expectations
                        @elseif($monthScore >= $seniorityBenchmark * 0.8)
                            ↑ Getting close
                        @else
                            ↓ Below target
                        @endif
                    </span>
                </div>
            </div>
            <div class="perf-criteria-list">
                @foreach($criteriaBreakdown as $item)
                <div class="perf-criteria-row">
                    <span class="perf-criteria-icon">{{ $item['icon'] ?? '📊' }}</span>
                    <span class="perf-criteria-label">{{ $item['label'] }}</span>
                    <div class="perf-criteria-bar-wrap">
                        <div class="perf-criteria-bar" data-width="{{ $item['score'] }}"></div>
                    </div>
                    <span class="perf-criteria-score">{{ $item['score'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ROW 3: MY TASKS WITH LOG LINKING --}}
    <div class="perf-card mt-lg">
        <div class="perf-card-header flex-between">
            <h3 class="perf-card-title">My Tasks &amp; Time Logged</h3>
            <a href="{{ route('tasks.index') }}" class="btn-secondary btn-sm">View All Tasks</a>
        </div>

        @if($myTasks->isEmpty())
        <div class="perf-empty">
            <p>No active tasks assigned to you.</p>
            <p class="text-muted-sm">Tasks assigned to you will appear here with their logged hours.</p>
        </div>
        @else
        <div class="perf-tasks-table-wrap">
            <table class="perf-tasks-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Est. Hours</th>
                        <th>Logged Hours</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myTasks as $task)
                    <tr class="perf-task-row">
                        <td>
                            <div class="perf-task-title">
                                @if($task->ticket_number)
                                <span class="perf-ticket">{{ $task->ticket_number }}</span>
                                @endif
                                <a href="{{ route('tasks.show', $task->id) }}" class="perf-task-name">{{ $task->title }}</a>
                            </div>
                        </td>
                        <td>
                            <span class="fv-badge fv-badge-gray">{{ $task->getStatusLabel() }}</span>
                        </td>
                        <td class="perf-hours-cell">
                            {{ $task->estimated_hours ? $task->estimated_hours . 'h' : '—' }}
                        </td>
                        <td class="perf-hours-cell {{ $task->isOverEstimate() ? 'perf-red' : '' }}">
                            {{ $task->actual_hours ? $task->actual_hours . 'h' : '0h' }}
                        </td>
                        <td>
                            @if($task->estimated_hours)
                            <div class="perf-task-progress-wrap">
                                <div class="perf-task-progress-bar" data-width="{{ $task->hoursProgress() }}"></div>
                                <span class="perf-task-progress-pct">{{ $task->hoursProgress() }}%</span>
                            </div>
                            @else
                            <span class="text-muted-sm">No estimate set</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('worklog.index') }}?task={{ $task->id }}" class="sa-btn sa-btn-view">+ Log Time</a>
                        </td>
                    </tr>
                    @if($task->workLogs->isNotEmpty())
                    <tr class="perf-task-logs-row">
                        <td colspan="6">
                            <div class="perf-task-logs">
                                @foreach($task->workLogs->take(3) as $log)
                                <div class="perf-task-log-item">
                                    <span class="perf-log-date">{{ \Carbon\Carbon::parse($log->log_date)->format('M j') }}</span>
                                    <span class="perf-log-title">{{ $log->title }}</span>
                                    <span class="perf-log-duration">{{ round($log->duration_minutes / 60, 1) }}h</span>
                                    <span class="perf-log-output">⭐ {{ $log->output_value }}/10</span>
                                </div>
                                @endforeach
                                @if($task->workLogs->count() > 3)
                                <div class="perf-log-more">+{{ $task->workLogs->count() - 3 }} more logs</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ROW 4: PERFORMANCE HISTORY --}}
    <div class="perf-card mt-lg">
        <h3 class="perf-card-title">Performance History</h3>
        <p class="perf-card-subtitle">Final scores shown with 2-month delay for fairness. Live estimates update daily.</p>

        @if($scoreHistory->isEmpty())
        <div class="perf-empty">
            <div class="perf-empty-icon">📈</div>
            <h4>No history yet</h4>
            <p class="text-muted-sm">Your performance scores will appear here after a 2-month delay. Keep logging work consistently to build a strong history.</p>
        </div>
        @else
        <div class="perf-history-grid">
            @foreach($scoreHistory as $score)
            <div class="perf-history-card">
                <div class="perf-history-month">{{ $score['month'] }}</div>
                <div class="perf-history-stars">
                    @for($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= $score['stars'] ? 'star-filled' : 'star-empty' }}">★</span>
                    @endfor
                </div>
                <div class="perf-history-label">{{ $score['label'] }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/performance.js') }}"></script>
@endpush
