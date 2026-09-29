@extends('layouts.app')
@section('title', 'Blockers')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dep.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Blockers &amp; Dependencies</h1>
        <p class="page-subtitle">Track what's slowing your team down and take action</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('fairness.index') }}" class="btn-secondary">View Fairness Report &rarr;</a>
    </div>
</div>

@if(session('success'))
    <div class="fv-alert fv-alert-success mb-lg">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="fv-alert fv-alert-warning mb-lg">{{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="fv-alert fv-alert-error mb-lg">
        <ul class="dep-error-list">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- Dispute Pattern Warning --}}
@if($disputePatternWarnings->count() > 0)
<div class="dep-dispute-pattern-warning">
    <div class="dep-dispute-pattern-title">⚠️ Ownership Dispute Pattern Detected</div>
    @foreach($disputePatternWarnings as $warning)
    <div class="dep-dispute-pattern-row">
        <strong>{{ $warning['user']?->name ?? 'Unknown' }}</strong> has disputed ownership
        <strong>{{ $warning['count'] }} times</strong>. Leadership review recommended.
    </div>
    @endforeach
</div>
@endif

{{-- Disputed Blockers Section --}}
@if($disputedBlockers->count() > 0)
<div class="dep-disputed-section">
    <div class="dep-disputed-header">
        <span class="dep-disputed-icon">⚠️</span>
        <h2 class="dep-disputed-title">
            Disputed Blockers
            <span class="dep-disputed-count">{{ $disputedBlockers->count() }} require CEO attention</span>
        </h2>
    </div>
    <div class="dep-disputed-list">
        @foreach($disputedBlockers as $blocker)
        <div class="dep-disputed-item">
            <div>
                <div class="dep-disputed-item-title">{{ $blocker->title }}</div>
                <div class="dep-disputed-item-meta">
                    <strong>{{ $blocker->blockedUser?->name }}</strong> reported →
                    <strong>{{ $blocker->blockingUser?->name ?? 'Unknown' }}</strong> disputes ownership
                    @if($blocker->dispute_raised_at)
                        · {{ $blocker->dispute_raised_at->diffForHumans() }}
                    @endif
                </div>
            </div>
            <a href="{{ route('dependency.show', $blocker->id) }}" class="dep-disputed-view-btn">
                View Full Story →
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Team Status Row --}}
@if($employeeStatuses->count() > 0)
<div class="fv-card dep-team-card">
    <div class="dep-team-header">
        <h2 class="dep-team-title">Team Status</h2>
        <span class="fv-badge fv-badge-warning">{{ $employeeStatuses->count() }} active</span>
    </div>
    <div class="dep-team-chips">
        @foreach($employeeStatuses as $es)
        <div class="dep-member-chip">
            <div class="dep-member-avatar">{{ strtoupper(substr($es->user->name, 0, 2)) }}</div>
            <div>
                <div class="dep-member-name">{{ $es->user->name }}</div>
                <div class="dep-member-status">
                    {{ str_replace('_', ' ', $es->status) }}
                    @if($es->ends_at) · until {{ $es->ends_at->format('M j') }}@endif
                </div>
            </div>
            @if(Auth::id() === $es->user_id || Auth::user()->can('manage_employee_status'))
            <form method="POST" action="{{ route('dependency.clearStatus', $es->user_id) }}" class="dep-clear-form">
                @csrf
                <button type="submit" class="dep-clear-btn">✕</button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Cross-Team Dependencies --}}
@if($crossTeamBlockers->count() > 0)
<div class="dep-cross-team">
    <div class="dep-cross-team-title">🏢 Cross-Team Dependencies (Same Company)</div>
    <p class="dep-cross-team-desc">These blockers involve people from your company but outside your team.</p>
    @foreach($crossTeamBlockers as $blocker)
    <div class="dep-cross-team-row">
        <div>
            <span class="dep-cross-team-name">{{ $blocker->title }}</span>
            <span class="dep-cross-team-meta">
                → {{ $blocker->external_person_name }}
                @if($blocker->external_person_company) ({{ $blocker->external_person_company }})@endif
            </span>
        </div>
        <div class="dep-cross-team-actions">
            <span class="dep-cross-team-age">{{ $blocker->daysOpen() }} days open</span>
            <a href="{{ route('dependency.show', $blocker->id) }}" class="dep-cross-team-link">View →</a>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Open Blockers Table --}}
<div class="fv-card mb-lg">
    <div class="dep-card-header">
        <h2 class="dep-card-title">
            Open Blockers
            <span class="fv-badge fv-badge-error" style="margin-left:8px;">{{ $openBlockers->count() }}</span>
        </h2>
    </div>

    @if($openBlockers->isEmpty())
        <div class="dep-empty">
            <div class="dep-empty-icon">✓</div>
            <div class="dep-empty-title">No open blockers</div>
            <div class="dep-empty-desc">Your team is unblocked.</div>
        </div>
    @else
    <div class="fv-table-wrap">
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Blocked</th>
                    <th>Blocking</th>
                    <th>Project</th>
                    <th>Priority</th>
                    <th>Age</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="dependency-list">
                @foreach($openBlockers as $blocker)
                <tr class="{{ $blocker->isOwnershipDisputed() ? 'dep-row-disputed' : '' }}">
                    <td>
                        @php
                            $typeLabels = [
                                'internal_person'     => ['Internal',    '#f4f4f5', '#3f3f46'],
                                'internal_other_team' => ['Same Co.',    '#dbeafe', '#1d4ed8'],
                                'external_vendor'     => ['Vendor',      '#fef3c7', '#d97706'],
                                'external_person'     => ['External',    '#fef3c7', '#d97706'],
                                'meeting_required'    => ['Meeting',     '#dbeafe', '#1d4ed8'],
                                'waiting_approval'    => ['Approval',    '#dbeafe', '#1d4ed8'],
                                'dependency_task'     => ['Dependency',  '#f4f4f5', '#3f3f46'],
                                'ownership_unclear'   => ['Disputed',    '#fef2f2', '#dc2626'],
                                'other'               => ['Other',       '#f4f4f5', '#3f3f46'],
                            ];
                            [$typeLabel, $typeBg, $typeColor] = $typeLabels[$blocker->blocker_type] ?? [ucfirst($blocker->blocker_type), '#f4f4f5', '#3f3f46'];
                        @endphp
                        <div class="dep-blocker-title">{{ $blocker->title }}</div>
                        <div class="dep-type-tags">
                            <span class="dep-type-badge" style="background:{{ $typeBg }};color:{{ $typeColor }};">{{ $typeLabel }}</span>
                            @if($blocker->isOwnershipDisputed())
                                <span class="dep-type-badge" style="background:#fef2f2;color:#dc2626;">⚠️ Disputed</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $blocker->blockedUser?->name ?? '—' }}</td>
                    <td>
                        @if($blocker->blockingUser)
                            {{ $blocker->blockingUser->name }}
                        @elseif($blocker->external_person_name)
                            <span class="dep-external-person">{{ $blocker->external_person_name }}
                            @if($blocker->external_person_company) ({{ $blocker->external_person_company }})@endif
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $blocker->project?->name ?? '—' }}</td>
                    <td>
                        @php
                            $pc = $blocker->priorityColor();
                            $priorityBadge = $pc === 'red' ? 'fv-badge fv-badge-error' : ($pc === 'amber' ? 'fv-badge fv-badge-warning' : 'fv-badge fv-badge-gray');
                        @endphp
                        <span class="{{ $priorityBadge }}" style="text-transform:capitalize;">{{ $blocker->priority }}</span>
                    </td>
                    <td>
                        @if($blocker->ageInDays() >= 3)
                            <span class="dep-age-old">🔥 {{ $blocker->ageInDays() }}d</span>
                        @else
                            <span class="dep-age-recent">{{ $blocker->ageInDays() }}d</span>
                        @endif
                    </td>
                    <td>
                        <div class="dep-action-wrap">
                            <a href="{{ route('dependency.show', $blocker->id) }}" class="dep-view-btn">View →</a>
                            @can('resolve_blockers')
                            <form method="POST" action="{{ route('dependency.resolve', $blocker->id) }}">
                                @csrf
                                <button type="submit" class="fv-btn fv-btn-primary dep-btn-sm"
                                        onclick="return confirm('Mark as resolved?')">Resolve</button>
                            </form>
                            @endcan
                            @can('escalate_blockers')
                            <form method="POST" action="{{ route('dependency.escalate', $blocker->id) }}">
                                @csrf
                                <button type="submit" class="fv-btn fv-btn-danger dep-btn-sm"
                                        onclick="return confirm('Escalate this blocker?')">Escalate</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <div id="dependency-pagination" class="pagination-wrapper"></div>
</div>

{{-- Report Blocker Form --}}
@can('create_blockers')
<div class="fv-card mb-lg" x-data="{ open: false }">
    <div class="dep-collapse-header" @click="open = !open">
        <h2 class="dep-card-title">+ Report a Blocker</h2>
        <span class="dep-collapse-toggle" x-text="open ? '▲ Collapse' : '▼ Expand'"></span>
    </div>
    <div x-show="open" x-transition class="p-lg">
        <form method="POST" action="{{ route('dependency.reportBlocker') }}" x-data="{ blockerType: '{{ old('blocker_type', '') }}' }">
            @csrf

            {{-- SECTION A — Basic Info --}}
            <div class="dep-form-grid-2">
                <div>
                    <label class="fv-label">Project</label>
                    <select name="project_id" required class="fv-input">
                        <option value="">Select project…</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fv-label">Blocker Type</label>
                    <select name="blocker_type" required class="fv-input" x-model="blockerType">
                        <option value="">Select type…</option>
                        <option value="internal_person">Waiting for team member</option>
                        <option value="internal_other_team">Same company, different team</option>
                        <option value="external_vendor">External vendor/supplier</option>
                        <option value="external_person">Person outside our company</option>
                        <option value="meeting_required">Needs a meeting/approval</option>
                        <option value="waiting_approval">Waiting for approval</option>
                        <option value="dependency_task">Task dependency</option>
                        <option value="ownership_unclear">Unclear who owns this</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="fv-label">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Brief description of the blocker" class="fv-input">
                </div>
                <div>
                    <label class="fv-label">Priority</label>
                    <select name="priority" required class="fv-input">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="mb-md">
                <label class="fv-label">Description</label>
                <textarea name="description" rows="3" required class="fv-input"
                          placeholder="Provide context and what's needed to unblock…"
                          style="resize:vertical;">{{ old('description') }}</textarea>
            </div>

            {{-- SECTION B — Who is blocking? --}}
            <div x-show="blockerType === 'internal_person' || blockerType === 'ownership_unclear'" class="mb-md">
                <div x-show="blockerType === 'ownership_unclear'" class="blocker-ownership-notice">
                    ⚠️ Select this when someone says "it's not my work" but you believe it IS their responsibility. This creates an accountability record.
                </div>
                <label class="fv-label" x-text="blockerType === 'ownership_unclear' ? 'Who do you believe should own this?' : 'Which team member is blocking you?'"></label>
                <select name="blocking_user_id" class="fv-input" id="blocker-blocking-user-select">
                    <option value="">No specific person</option>
                    @foreach($members as $member)
                    <option value="{{ $member->id }}" {{ old('blocking_user_id') == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Affected Task --}}
            <div class="mb-md">
                <label class="blocker-form-label">
                    Affected Task
                    <span class="blocker-form-optional">(optional)</span>
                </label>
                <select name="task_id" class="fv-input">
                    <option value="">No specific task</option>
                    @foreach($myTasks ?? [] as $task)
                    <option value="{{ $task->id }}" {{ old('task_id') == $task->id ? 'selected' : '' }}>
                        {{ $task->ticket_number ? $task->ticket_number . ' — ' : '' }}{{ $task->title }}
                    </option>
                    @endforeach
                </select>
                <p class="blocker-form-hint">Link this blocker to a task to track time impact</p>
            </div>

            {{-- Impact Level --}}
            <div class="mb-md">
                <label class="blocker-form-label">Impact Level *</label>
                <select name="impact_level" class="fv-input" required>
                    <option value="just_me" {{ old('impact_level', 'just_me') === 'just_me' ? 'selected' : '' }}>Just me — only I am blocked</option>
                    <option value="my_team" {{ old('impact_level') === 'my_team' ? 'selected' : '' }}>My team — multiple people blocked</option>
                    <option value="cross_team" {{ old('impact_level') === 'cross_team' ? 'selected' : '' }}>Cross-team — affects multiple departments</option>
                    <option value="critical" {{ old('impact_level') === 'critical' ? 'selected' : '' }}>Critical — blocking entire project/release</option>
                </select>
            </div>

            <div x-show="blockerType === 'internal_other_team'" class="mb-md">
                <div class="dep-warn-notice">
                    💼 This person works at your company but is not part of your team in OutraqHQ. Their details will be recorded for tracking.
                </div>
                <div class="dep-form-grid-2">
                    <div>
                        <label class="fv-label">Their Full Name</label>
                        <input type="text" name="external_person_name" value="{{ old('external_person_name') }}"
                               placeholder="e.g. John Smith" class="fv-input">
                    </div>
                    <div>
                        <label class="fv-label">Their Department / Team</label>
                        <input type="text" name="external_person_company" value="{{ old('external_person_company') }}"
                               placeholder="e.g. Finance, HR, Legal, Design" class="fv-input">
                    </div>
                    <div class="dep-form-grid-span2">
                        <label class="fv-label">Their Email or Slack (optional)</label>
                        <input type="text" name="external_person_contact" value="{{ old('external_person_contact') }}"
                               placeholder="john@company.com" class="fv-input">
                    </div>
                </div>
                <div class="dep-info-box">
                    💡 <strong>Why this matters:</strong> If this person consistently blocks your team, the system builds a pattern record. Leadership can then address the cross-team dependency issue.
                </div>
            </div>

            <div x-show="blockerType === 'external_person' || blockerType === 'external_vendor'" class="mb-md">
                <div class="dep-form-grid-2">
                    <div>
                        <label class="fv-label">Their Name</label>
                        <input type="text" name="external_person_name" value="{{ old('external_person_name') }}"
                               placeholder="Full name" class="fv-input">
                    </div>
                    <div>
                        <label class="fv-label">Their Company / Department</label>
                        <input type="text" name="external_person_company" value="{{ old('external_person_company') }}"
                               placeholder="Company or team name" class="fv-input">
                    </div>
                    <div class="dep-form-grid-span2">
                        <label class="fv-label">Their Email / Contact (optional)</label>
                        <input type="text" name="external_person_contact" value="{{ old('external_person_contact') }}"
                               placeholder="email@company.com or Slack handle" class="fv-input">
                    </div>
                </div>
            </div>

            {{-- SECTION C — Evidence --}}
            <div class="mb-md">
                <label class="fv-label">Evidence &amp; Reference <span class="dep-evidence-optional">(optional)</span></label>
                <textarea name="evidence_notes" rows="2" class="fv-input"
                          placeholder="Ticket number, email thread, Slack message, meeting notes…"
                          style="resize:vertical;">{{ old('evidence_notes') }}</textarea>
                <div class="dep-evidence-hint">This becomes your proof. Document everything.</div>
            </div>

            {{-- SECTION D — Due Date --}}
            <div class="dep-form-margin">
                <label class="fv-label">Due Date (optional)</label>
                <input type="date" name="due_date" value="{{ old('due_date') }}" class="fv-input" style="width:auto;">
            </div>

            <button type="submit" class="fv-btn fv-btn-primary">Report Blocker</button>

            <div class="blocker-notify-preview" id="blocker-notify-preview">
                <span class="blocker-notify-icon">🔔</span>
                <span class="blocker-notify-text">
                    Submitting will notify:
                    <strong id="blocker-notify-name">Select a team member above</strong>
                    and your manager
                </span>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- Set Employee Status --}}
@can('manage_employee_status')
<div class="fv-card mb-lg" x-data="{ open: false }">
    <div class="dep-collapse-header" @click="open = !open">
        <h2 class="dep-card-title">Set Employee Status</h2>
        <span class="dep-collapse-toggle" x-text="open ? '▲ Collapse' : '▼ Expand'"></span>
    </div>
    <div x-show="open" x-transition class="p-lg">
        <form method="POST" action="{{ route('dependency.setStatus') }}">
            @csrf
            <div class="dep-form-grid-2">
                <div>
                    <label class="fv-label">Team Member</label>
                    <select name="user_id" required class="fv-input">
                        <option value="">Select member…</option>
                        @foreach($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fv-label">Status</label>
                    <select name="status" required class="fv-input">
                        <option value="on_leave">On Leave</option>
                        <option value="resigned">Resigned</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="fv-label">Starts At</label>
                    <input type="date" name="starts_at" value="{{ old('starts_at', now()->toDateString()) }}" required class="fv-input">
                </div>
                <div>
                    <label class="fv-label">Ends At (optional)</label>
                    <input type="date" name="ends_at" value="{{ old('ends_at') }}" class="fv-input">
                </div>
            </div>
            <div class="dep-form-margin">
                <label class="fv-label">Reason (optional)</label>
                <input type="text" name="reason" value="{{ old('reason') }}"
                       placeholder="e.g. Annual leave, medical, etc." class="fv-input">
            </div>
            <button type="submit" class="fv-btn fv-btn-primary">Set Status</button>
        </form>
    </div>
</div>
@endcan

{{-- Resolved Blockers --}}
@if($resolvedBlockers->count() > 0)
<div class="fv-card" x-data="{ open: false }">
    <div class="dep-resolved-header" @click="open = !open">
        <h2 class="dep-card-title">
            Recently Resolved
            <span class="dep-resolved-count">({{ $resolvedBlockers->count() }})</span>
        </h2>
        <span class="dep-collapse-toggle" x-text="open ? '▲ Collapse' : '▼ Show'"></span>
    </div>
    <div x-show="open" x-transition>
        <div class="fv-table-wrap">
            <table class="fv-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Blocked</th>
                        <th>Resolved By</th>
                        <th>Days Taken</th>
                        <th>Resolved At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resolvedBlockers as $blocker)
                    <tr>
                        <td>
                            <a href="{{ route('dependency.show', $blocker->id) }}" class="dep-resolved-link">{{ $blocker->title }}</a>
                        </td>
                        <td>{{ $blocker->blockedUser?->name ?? '—' }}</td>
                        <td>{{ $blocker->resolvedBy?->name ?? '—' }}</td>
                        <td>
                            @if($blocker->days_to_resolve !== null)
                                <span class="{{ $blocker->days_to_resolve > 5 ? 'dep-days-old' : 'dep-days-recent' }}">
                                    {{ $blocker->days_to_resolve }}d
                                </span>
                            @else
                                <span class="dep-age-recent">—</span>
                            @endif
                        </td>
                        <td class="dep-resolved-at">{{ $blocker->resolved_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

</div>

@push('scripts')
<script src="{{ asset('js/dependency-page.js') }}"></script>
@endpush
@endsection
