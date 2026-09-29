@extends('layouts.app')

@section('content')
<div class="page-container">

{{-- Breadcrumb + Header --}}
<div class="mb-lg">
    <div style="font-size:0.8rem;color:#94a3b8;margin-bottom:8px;">
        <a href="{{ route('departments.index') }}" style="color:#94a3b8;text-decoration:none;font-weight:600;">Departments</a>
        <span style="margin:0 6px;">›</span>
        <span style="color:#0f172a;">{{ $dept->name }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="width:14px;height:14px;border-radius:50%;background:{{ $dept->color }};flex-shrink:0;"></div>
            <h1 style="font-size:1.75rem;font-weight:800;color:#0f172a;letter-spacing:-0.025em;margin:0;">{{ $dept->name }}</h1>
            <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:3px 10px;border-radius:20px;background:#f4f4f5;color:#52525b;">
                {{ $dept->getTypeLabel() }}
            </span>
            @php
                $modeBg    = match($dept->work_mode) { 'github' => '#18181b', 'hybrid' => '#eff6ff', default => '#f4f4f5' };
                $modeColor = match($dept->work_mode) { 'github' => '#ffffff', 'hybrid' => '#1d4ed8', default => '#52525b' };
                $modeLabel = match($dept->work_mode) { 'github' => 'GitHub', 'hybrid' => 'Hybrid', default => 'Manual' };
            @endphp
            <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:3px 10px;border-radius:20px;background:{{ $modeBg }};color:{{ $modeColor }};">
                {{ $modeLabel }}
            </span>
        </div>
        @can('manage_organization')
        <a href="{{ route('departments.edit', $dept->id) }}" class="fv-btn fv-btn-secondary">Edit Department</a>
        @endcan
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="fv-alert fv-alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="fv-alert fv-alert-warning" style="margin-bottom:20px;">{{ $errors->first() }}</div>
@endif

{{-- Stats row --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="fv-card" style="padding:20px;border-top:3px solid {{ $dept->color }};">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Total Members</div>
        <div style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ $dept->users->count() }}</div>
    </div>
    <div class="fv-card" style="padding:20px;border-top:3px solid #059669;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Today's Logs</div>
        <div style="font-size:2rem;font-weight:800;color:{{ $todayLogs > 0 ? '#059669' : '#94a3b8' }};line-height:1;">{{ $todayLogs }}</div>
    </div>
    <div class="fv-card" style="padding:20px;border-top:3px solid #3b82f6;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">This Week's Hours</div>
        <div style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ number_format($weekHours / 60, 1) }}</div>
    </div>
    <div class="fv-card" style="padding:20px;border-top:3px solid #d97706;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Avg Output Score</div>
        <div style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ number_format($avgOutput, 1) }}<span style="font-size:1rem;color:#94a3b8;">/10</span></div>
    </div>
</div>

{{-- Two-column layout --}}
<div style="display:grid;grid-template-columns:1fr 400px;gap:20px;margin-bottom:24px;">

    {{-- LEFT: Members table --}}
    <div>
        <div class="fv-card" style="margin-bottom:20px;">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Members</div>
                    <div class="fv-section-sub">{{ $dept->users->count() }} team members</div>
                </div>
                <span class="fv-badge fv-badge-gray">{{ $dept->users->count() }}</span>
            </div>
            @if($dept->users->isEmpty())
            <div class="fv-empty">
                <div class="fv-empty-title">No members yet</div>
                <div class="fv-empty-desc">Add members using the form below.</div>
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="fv-table">
                    <thead><tr><th>Member</th><th>Role</th><th>GitHub</th><th>Today's Logs</th><th></th></tr></thead>
                    <tbody>
                        @foreach($dept->users as $member)
                        @php
                            $memberTodayLogs = \App\Models\WorkLog::where('user_id', $member->id)
                                ->where('log_date', today())->count();
                        @endphp
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:50%;background:{{ $dept->color }}20;border:1.5px solid {{ $dept->color }}40;display:flex;align-items:center;justify-content:center;font-size:0.68rem;font-weight:800;color:{{ $dept->color }};flex-shrink:0;">
                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:0.85rem;color:#0f172a;">{{ $member->name }}</div>
                                        <div style="font-size:0.72rem;color:#94a3b8;">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="fv-badge fv-badge-gray" style="font-size:0.68rem;">{{ ucfirst(str_replace('_',' ',$member->role)) }}</span></td>
                            <td>
                                @if($member->github_username)
                                <span style="font-family:monospace;font-size:0.78rem;color:#64748b;">&#64;{{ $member->github_username }}</span>
                                @else
                                <span style="color:#cbd5e1;font-size:0.78rem;">—</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:0.82rem;font-weight:700;color:{{ $memberTodayLogs > 0 ? '#059669' : '#94a3b8' }};">{{ $memberTodayLogs }}</span>
                            </td>
                            <td>
                                @can('manage_organization')
                                <form method="POST" action="{{ route('departments.remove-member', [$dept->id, $member->id]) }}"
                                      onsubmit="return confirm('Remove {{ $member->name }} from this department?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:0.72rem;color:#dc2626;background:none;border:none;cursor:pointer;padding:2px 6px;border-radius:4px;font-family:inherit;"
                                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">Remove</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Add member form --}}
            @can('manage_organization')
            @if($nonMembers->isNotEmpty())
            <div style="padding:16px;border-top:1px solid #f1f5f9;">
                <div style="font-size:0.8rem;font-weight:700;color:#0f172a;margin-bottom:10px;">Add Member</div>
                <form method="POST" action="{{ route('departments.assign') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                    @csrf
                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                    <select name="user_id" style="flex:1;min-width:180px;padding:8px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.85rem;font-family:inherit;background:white;color:#0f172a;">
                        <option value="">Select team member…</option>
                        @foreach($nonMembers as $nm)
                        <option value="{{ $nm->id }}">{{ $nm->name }} ({{ ucfirst($nm->role) }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="fv-btn fv-btn-primary" style="font-size:0.82rem;white-space:nowrap;">Add to Department</button>
                </form>
            </div>
            @endif
            @endcan
        </div>

        {{-- Work Mode Settings --}}
        @can('manage_organization')
        <div class="fv-card" style="padding:20px;">
            <div style="font-size:0.9rem;font-weight:800;color:#0f172a;margin-bottom:4px;">Work Mode Settings</div>
            <div style="font-size:0.8rem;color:#94a3b8;margin-bottom:16px;">How does this department track work?</div>
            <form method="POST" action="{{ route('departments.update', $dept->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="name" value="{{ $dept->name }}">
                <input type="hidden" name="type" value="{{ $dept->type }}">
                <input type="hidden" name="color" value="{{ $dept->color }}">
                <input type="hidden" name="description" value="{{ $dept->description }}">
                <input type="hidden" name="head_user_id" value="{{ $dept->head_user_id }}">
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                    @foreach([['github','GitHub Only','Track commits & PRs'],['manual','Manual Only','Daily work logs'],['hybrid','Hybrid','Both methods']] as [$val,$label,$sub])
                    <label style="flex:1;min-width:140px;padding:12px;border:2px solid {{ $dept->work_mode === $val ? '#18181b' : '#e4e4e7' }};border-radius:10px;cursor:pointer;display:block;">
                        <input type="radio" name="work_mode" value="{{ $val }}" {{ $dept->work_mode === $val ? 'checked' : '' }} style="display:none;">
                        <div style="font-size:0.82rem;font-weight:700;color:#0f172a;">{{ $label }}</div>
                        <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;">{{ $sub }}</div>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="fv-btn fv-btn-primary" style="font-size:0.82rem;">Save Work Mode</button>
            </form>
        </div>
        @endcan
    </div>

    {{-- RIGHT: Recent logs + metrics --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Recent Work Logs --}}
        <div class="fv-card">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Recent Work Logs</div>
                    <div class="fv-section-sub">Last 10 entries across all members</div>
                </div>
                <a href="{{ route('worklog.index') }}" style="font-size:0.75rem;color:#64748b;text-decoration:none;font-weight:600;">View all →</a>
            </div>
            @if($recentLogs->isEmpty())
            <div class="fv-empty">
                <div class="fv-empty-title">No logs yet</div>
                <div class="fv-empty-desc">Members can log work from the Work Log page.</div>
            </div>
            @else
            <div style="display:flex;flex-direction:column;gap:0;">
                @foreach($recentLogs as $log)
                @php $catColor = $log->getCategoryColor(); @endphp
                <div style="padding:12px 20px;border-bottom:1px solid #f8fafc;">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $catColor }};flex-shrink:0;margin-top:5px;"></div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.82rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $log->title }}</div>
                            <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span style="font-weight:600;color:#475569;">{{ $log->user->name }}</span>
                                <span style="padding:1px 6px;border-radius:10px;background:{{ $catColor }}18;color:{{ $catColor }};font-weight:700;font-size:0.65rem;">{{ $log->getCategoryLabel() }}</span>
                                @if($log->duration_minutes)
                                <span>{{ $log->getDurationFormatted() }}</span>
                                @endif
                            </div>
                        </div>
                        <div style="font-size:0.7rem;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $log->log_date->format('M j') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Department Metrics --}}
        @php $activeMetrics = $dept->metrics->where('is_active', true); @endphp
        @if($activeMetrics->isNotEmpty())
        <div class="fv-card" style="padding:20px;">
            <div style="font-size:0.9rem;font-weight:800;color:#0f172a;margin-bottom:4px;">This Week's Metrics</div>
            <div style="font-size:0.78rem;color:#94a3b8;margin-bottom:16px;">Cumulative totals from all members</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($activeMetrics as $metric)
                @php
                    $total    = $metricTotals[$metric->id] ?? 0;
                    $maxValue = max(1, $total * 1.2);
                    $pct      = min(100, ($total / $maxValue) * 100);
                @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <span style="font-size:0.8rem;font-weight:600;color:#374151;">{{ $metric->metric_label }}</span>
                        <span style="font-size:0.82rem;font-weight:800;color:#0f172a;">
                            @if($metric->metric_type === 'currency')${{ number_format($total, 0) }}
                            @elseif($metric->metric_type === 'hours'){{ number_format($total, 1) }}h
                            @elseif($metric->metric_type === 'percentage'){{ number_format($total, 1) }}%
                            @else{{ number_format($total, 0) }}
                            @endif
                        </span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $dept->color }};border-radius:3px;transition:width 0.3s;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

</div>
@endsection
