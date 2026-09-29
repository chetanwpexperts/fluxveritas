@extends('layouts.app')
@section('title', 'Work Log')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/worklog.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">My Work Log</h1>
        <p class="page-subtitle">{{ now()->format('l, F j, Y') }} &middot; Track and review your contributions</p>
    </div>
    @if(in_array(auth()->user()->role, ['owner','admin','team_lead']))
    <div class="page-header-right">
        <a href="{{ route('worklog.team') }}" class="btn-secondary">Team View</a>
    </div>
    @endif
</div>

@if(session('success'))
<div class="fv-alert fv-alert-success mb-md">{{ session('success') }}</div>
@endif

{{-- Stats Bar --}}
@php
    $weekCategories = $weekLogs->groupBy('category');
    $meetingMins    = ($weekCategories->get('meeting') ?? collect())->sum('duration_minutes');
    $devMins        = ($weekCategories->get('development') ?? collect())->sum('duration_minutes');
    $reviewMins     = ($weekCategories->get('code_review') ?? collect())->sum('duration_minutes');
@endphp
<div class="wl-stats-bar">
    <div class="wl-stat-card wl-stat-card--total">
        <div class="wl-stat-label">Total Hours</div>
        <div class="wl-stat-value">{{ number_format($weekMinutes / 60, 1) }}<span class="wl-stat-unit">h</span></div>
        <div class="wl-stat-sub">This week</div>
    </div>
    <div class="wl-stat-card wl-stat-card--meetings">
        <div class="wl-stat-label">Meetings</div>
        <div class="wl-stat-value">{{ number_format($meetingMins / 60, 1) }}<span class="wl-stat-unit">h</span></div>
        <div class="wl-stat-sub">{{ $weekCategories->get('meeting')?->count() ?? 0 }} sessions</div>
    </div>
    <div class="wl-stat-card wl-stat-card--dev">
        <div class="wl-stat-label">Development</div>
        <div class="wl-stat-value">{{ number_format($devMins / 60, 1) }}<span class="wl-stat-unit">h</span></div>
        <div class="wl-stat-sub">{{ $weekCategories->get('development')?->count() ?? 0 }} tasks</div>
    </div>
    <div class="wl-stat-card wl-stat-card--reviews">
        <div class="wl-stat-label">Reviews</div>
        <div class="wl-stat-value">{{ number_format($reviewMins / 60, 1) }}<span class="wl-stat-unit">h</span></div>
        <div class="wl-stat-sub">{{ $weekCategories->get('code_review')?->count() ?? 0 }} reviews</div>
    </div>
    <div class="wl-stat-card wl-stat-card--output">
        <div class="wl-stat-label">Avg Output</div>
        <div class="wl-stat-value">{{ number_format($avgOutput, 1) }}<span class="wl-stat-unit">/10</span></div>
        <div class="wl-stat-sub">Quality score</div>
    </div>
</div>

{{-- Tab Navigation --}}
<div class="wl-tabs">
    <nav class="wl-tabs-nav">
        <button class="wl-tab-btn wl-tab-btn--active" data-tab="quicklog">⚡ Quick Log</button>
        <button class="wl-tab-btn" data-tab="weekly">📅 Weekly Grid</button>
        <button class="wl-tab-btn" data-tab="templates">📋 Templates</button>
    </nav>
</div>

{{-- ═══ TAB 1: QUICK LOG ═══ --}}
<div id="tab-quicklog" class="wl-tab-panel wl-tab-panel--active">
    <div class="wl-card">
        <div class="wl-card-title-bar">
            <div class="wl-card-title">Log Activity with AI</div>
            <button id="wl-copy-yesterday" class="wl-copy-btn">↑ Copy from yesterday</button>
        </div>

        <textarea id="wl-quick-text" class="wl-quick-textarea" rows="4"
                  placeholder="Describe what you worked on today… e.g. 'Fixed the login bug and reviewed 2 PRs for the auth module'"></textarea>

        <div class="wl-quick-controls">
            <div class="wl-quick-field">
                <span class="wl-field-label">Date</span>
                <input type="date" id="wl-quick-date" class="wl-quick-date" value="{{ today()->toDateString() }}">
            </div>

            <div class="wl-quick-field">
                <span class="wl-field-label">Duration</span>
                <div id="wl-hour-pills" class="wl-pill-group">
                    <button class="wl-pill" data-minutes="15">15m</button>
                    <button class="wl-pill" data-minutes="30">30m</button>
                    <button class="wl-pill" data-minutes="45">45m</button>
                    <button class="wl-pill" data-minutes="60">1h</button>
                    <button class="wl-pill" data-minutes="90">1.5h</button>
                    <button class="wl-pill" data-minutes="120">2h</button>
                    <button class="wl-pill" data-minutes="180">3h</button>
                    <button class="wl-pill" data-minutes="240">4h</button>
                    <button class="wl-pill" data-minutes="custom">Custom</button>
                    <input type="number" id="wl-custom-minutes" class="wl-custom-input wl-hidden"
                           placeholder="mins" min="15" max="480">
                </div>
            </div>

            <div class="wl-quick-field">
                <span class="wl-field-label">Output (1–10)</span>
                <div id="wl-output-pills" class="wl-pill-group">
                    @for($i = 1; $i <= 10; $i++)
                    <button class="wl-pill wl-pill--output" data-output="{{ $i }}">{{ $i }}</button>
                    @endfor
                </div>
            </div>
        </div>

        <div id="wl-ai-result" class="wl-ai-result">
            <div class="wl-ai-result-icon">✓</div>
            <div>
                <div id="wl-ai-title" class="wl-ai-result-title"></div>
                <div id="wl-ai-cat" class="wl-ai-result-cat"></div>
            </div>
        </div>

        <div class="quicklog-task-row">
            <label class="quicklog-label">
                Link to Task <span class="quicklog-optional">(optional)</span>
            </label>
            <select id="quicklog-task-select" class="quicklog-task-select">
                <option value="">No task — general work</option>
            </select>
        </div>

        <div class="wl-quick-actions">
            <button id="wl-quick-submit" class="wl-btn-primary">Log with AI →</button>
        </div>
    </div>
</div>

{{-- ═══ TAB 2: WEEKLY GRID ═══ --}}
<div id="tab-weekly" class="wl-tab-panel">
    <div class="wl-card">
        <div class="wl-weekly-nav">
            <button id="wl-week-prev" class="wl-week-nav-btn">← Previous</button>
            <span id="wl-week-label" class="wl-week-label">Loading…</span>
            <button id="wl-week-next" class="wl-week-nav-btn">Next →</button>
        </div>
        <div class="wl-weekly-scroll">
            <table class="wl-weekly-table">
                <thead>
                    <tr>
                        <th class="wl-th">Day</th>
                        <th class="wl-th">Category</th>
                        <th class="wl-th wl-th--wide">What did you work on?</th>
                        <th class="wl-th">Mins</th>
                        <th class="wl-th">Output</th>
                        <th class="wl-th"></th>
                    </tr>
                </thead>
                <tbody id="wl-weekly-body"></tbody>
            </table>
        </div>
        <div class="wl-weekly-footer">
            <button id="wl-bulk-submit" class="wl-btn-primary">Save Week's Logs</button>
        </div>
    </div>
</div>

{{-- ═══ TAB 3: TEMPLATES ═══ --}}
<div id="tab-templates" class="wl-tab-panel">
    <div class="wl-templates-layout">
        <div class="wl-templates-list">
            <div class="wl-card-title-bar">
                <div class="wl-card-title">Saved Templates</div>
            </div>
            <div id="wl-templates-empty" class="wl-empty-state wl-hidden">
                <div class="wl-empty-icon">📋</div>
                <div class="wl-empty-text">No templates yet. Save a common task using the form on the right.</div>
            </div>
            <div id="wl-templates-grid"></div>
        </div>

        <div class="wl-card">
            <div class="wl-card-title-bar">
                <div class="wl-card-title">New Template</div>
            </div>

            <div class="wl-form-group">
                <label class="wl-field-label" for="tpl-name">Template Name</label>
                <input type="text" id="tpl-name" class="wl-field-input" placeholder="e.g. Daily Standup">
            </div>

            <div class="wl-form-group">
                <label class="wl-field-label" for="tpl-category">Category</label>
                <select id="tpl-category" class="wl-field-select">
                    <option value="">Select category…</option>
                    @foreach($workLogCategories as $category)
                    <option value="{{ Str::slug($category, '_') }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="wl-form-group">
                <label class="wl-field-label" for="tpl-title">Default Title</label>
                <input type="text" id="tpl-title" class="wl-field-input" placeholder="e.g. Team standup meeting">
            </div>

            <div class="wl-form-group">
                <label class="wl-field-label" for="tpl-description">Description (optional)</label>
                <textarea id="tpl-description" class="wl-field-textarea" rows="2" placeholder="Additional notes…"></textarea>
            </div>

            <div class="wl-form-row wl-form-group">
                <div>
                    <label class="wl-field-label" for="tpl-duration">Duration (mins)</label>
                    <input type="number" id="tpl-duration" class="wl-field-input" placeholder="e.g. 30" min="1" max="480">
                </div>
                <div>
                    <label class="wl-field-label" for="tpl-output">Output (1–10)</label>
                    <input type="number" id="tpl-output" class="wl-field-input" placeholder="5" min="1" max="10">
                </div>
            </div>

            <button id="wl-save-template" class="wl-btn-primary wl-btn-full">Save Template</button>
        </div>
    </div>
</div>

{{-- ═══ HISTORY TIMELINE ═══ --}}
<div class="wl-history" id="worklog-history-list">
    <div class="wl-history-header">
        <div class="wl-card-title">Recent Activity</div>
        <div class="wl-history-sub">Last 30 days · {{ $totalLogs }} {{ Str::plural('entry', $totalLogs) }} · {{ number_format($totalMinutes / 60, 1) }}h total</div>
    </div>

    @if($byDate->isEmpty())
    <div class="fv-card wl-empty-full">
        <svg class="wl-empty-icon-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
        </svg>
        <div class="wl-empty-title">No logs yet</div>
        <div class="wl-empty-desc">Start logging your daily work to build a record of your contributions.</div>
    </div>
    @else
    @foreach($byDate as $date => $dayLogs)
    @php
        $dayMinutes = $dayLogs->sum('duration_minutes');
        $carbon     = \Carbon\Carbon::parse($date);
        $isToday    = $carbon->isToday();
    @endphp
    <div class="wl-day-group">
        <div class="wl-day-header{{ $isToday ? ' wl-day-header--today' : '' }}">
            <div class="wl-day-label">{{ $isToday ? 'Today' : $carbon->format('l, M j') }}</div>
            <div class="wl-day-rule"></div>
            @if($dayMinutes)
            <div class="wl-day-hours">{{ number_format($dayMinutes / 60, 1) }}h</div>
            @endif
        </div>

        <div class="fv-card wl-day-card">
            @foreach($dayLogs as $log)
            @php $catClass = str_replace('_', '-', $log->category); @endphp
            <div class="wl-log-entry">
                <div class="wl-log-strip cat-strip-{{ $catClass }}"></div>
                <div class="wl-log-body">
                    <div class="wl-log-top">
                        <div class="wl-log-info">
                            <div class="wl-log-title">{{ $log->title }}</div>
                            <div class="wl-log-meta">
                                <span class="wl-cat-badge cat-badge-{{ $catClass }}">{{ $log->getCategoryLabel() }}</span>
                                @if($log->duration_minutes)
                                <span class="wl-log-duration">{{ $log->getDurationFormatted() }}</span>
                                @endif
                                @if($log->output_value)
                                <span class="wl-log-output">★ {{ $log->output_value }}/10</span>
                                @endif
                                @if($log->project)
                                <span class="wl-log-project">{{ $log->project->name }}</span>
                                @endif
                            </div>
                            @if($log->description)
                            <div class="wl-log-desc">{{ Str::limit($log->description, 120) }}</div>
                            @endif
                        </div>
                        <div class="wl-log-actions">
                            <form method="POST" action="{{ route('worklog.destroy', $log->id) }}" class="wl-delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="wl-delete-btn" title="Delete log entry">✕</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
    @endif
</div>

<div id="worklog-history-pagination" class="pagination-wrapper"></div>

</div>
@endsection

@push('scripts')
<div id="worklog-data" data-categories="{{ json_encode($workLogCategories) }}" class="wl-hidden"></div>
<script>
window.WL_ROUTES = {
    quick:           '{{ route("worklog.quick") }}',
    bulk:            '{{ route("worklog.bulk") }}',
    weekLogs:        '{{ route("worklog.week-logs") }}',
    yesterday:       '{{ route("worklog.yesterday") }}',
    templates:       '{{ route("worklog.templates.index") }}',
    templateStore:   '{{ route("worklog.templates.store") }}',
    templateUseBase: '{{ url("/work-log/templates") }}',
};
window.WL_TODAY = '{{ today()->toDateString() }}';
</script>
<script src="{{ asset('js/worklog.js') }}"></script>
<script src="{{ asset('js/worklog-pagination.js') }}"></script>
@endpush
