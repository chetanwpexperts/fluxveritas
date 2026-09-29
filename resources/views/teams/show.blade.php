@extends('layouts.app')
@section('title', $team->name)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <div class="team-breadcrumb">
                <a href="{{ route('teams.index') }}" class="team-back-link">← All Teams</a>
                <span class="team-breadcrumb-sep">/</span>
                <span>{{ $team->department?->name }}</span>
            </div>
            <h1 class="page-title">{{ $team->name }}</h1>
            <p class="page-subtitle">{{ $team->description ?? 'No description' }}</p>
        </div>
        @if($canManage)
        <div class="page-header-right" style="display:flex;gap:8px">
            <a href="{{ route('teams.edit', $team->id) }}" class="btn-secondary">Edit Team</a>
        </div>
        @endif
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="fv-alert fv-alert-error mb-md">❌ {{ session('error') }}</div>
    @endif

    <div class="dash-grid-4 mb-md">
        <div class="fv-stat">
            <div class="fv-stat-number">{{ $team->members->count() }}/10</div>
            <div class="fv-stat-label">Team Members</div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-number {{ $loggedToday === $team->members->count() && $team->members->count() > 0 ? 'text-green' : 'text-amber' }}">
                {{ $loggedToday }}/{{ $team->members->count() }}
            </div>
            <div class="fv-stat-label">Logged Today</div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-number">{{ $activeTasks }}</div>
            <div class="fv-stat-label">Active Tasks</div>
        </div>
        <div class="fv-stat {{ $openBlockers > 0 ? 'fv-stat-warn' : '' }}">
            <div class="fv-stat-number">{{ $openBlockers }}</div>
            <div class="fv-stat-label">Open Blockers</div>
        </div>
    </div>

    @if($activeSprint)
    <div class="fv-card mb-md" style="padding:20px">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:15px;font-weight:700;color:#18181b">
                    🚀 Active Sprint: {{ $activeSprint->name }}
                </div>
                <div class="text-muted-xs" style="margin-top:4px">
                    Ends: {{ $activeSprint->end_date ? \Carbon\Carbon::parse($activeSprint->end_date)->format('M j, Y') : 'No deadline' }}
                </div>
            </div>
            <a href="{{ route('sprints.show', $activeSprint->id) }}" class="btn-secondary">View Sprint →</a>
        </div>
    </div>
    @endif

    <div class="fv-card mb-md">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">Team Members</div>
                <div class="fv-section-sub">{{ $team->members->count() }}/10 · Min 3 required</div>
            </div>
            @if($canManage && !$team->isAtCapacity())
            <button onclick="toggleAddMember()" class="btn-primary btn-sm">+ Add Member</button>
            @endif
        </div>

        @if($canManage && !$team->isAtCapacity())
        <div id="add-member-form" style="display:none;padding:16px 20px;background:#f9fafb;border-bottom:1px solid #f4f4f5">
            <form method="POST" action="{{ route('teams.members.add', $team->id) }}"
                style="display:flex;gap:10px;align-items:center">
                @csrf
                <select name="user_id" class="task-filter-select" style="flex:1;max-width:320px" required>
                    <option value="">Select member to add...</option>
                    @php
                    $available = \App\Models\User::where('organization_id', auth()->user()->organization_id)
                        ->where('is_active', true)
                        ->where(fn($q) => $q->whereNull('team_id')->orWhere('team_id', $team->id))
                        ->whereNotIn('id', $team->members->pluck('id'))
                        ->orderBy('name')->get();
                    @endphp
                    @foreach($available as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->getRoleNames()->first() ?? 'employee' }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary btn-sm">Add to Team</button>
                <button type="button" onclick="toggleAddMember()" class="btn-secondary btn-sm">Cancel</button>
            </form>
        </div>
        @endif

        <table class="fv-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Role</th>
                    <th>Designation</th>
                    <th>Status Today</th>
                    <th>Active Tasks</th>
                    @if($canManage)<th>Actions</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($memberStats as $ms)
                @php $m = $ms['user']; @endphp
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="team-member-avatar">{{ strtoupper(substr($m->name, 0, 1)) }}</div>
                            <div>
                                <div class="fv-fw-600">
                                    {{ $m->name }}
                                    @if($team->team_lead_id === $m->id)
                                    <span class="fv-badge" style="background:#f0fdf4;color:#166534;font-size:11px">Lead</span>
                                    @endif
                                </div>
                                <div class="text-muted-xs">{{ $m->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="fv-badge fv-badge-gray">{{ ucfirst($ms['role'] ?? 'employee') }}</span></td>
                    <td class="text-muted-xs">{{ $m->job_title ?? $m->designation ?? '—' }}</td>
                    <td>
                        @if($ms['logged_today'])
                        <span style="color:#16a34a;font-weight:600;font-size:13px">✅ Logged</span>
                        @else
                        <span style="color:#dc2626;font-weight:600;font-size:13px">❌ Not logged</span>
                        @endif
                    </td>
                    <td class="fv-fw-600">{{ $ms['active_tasks'] }}</td>
                    @if($canManage)
                    <td>
                        @if($team->team_lead_id !== $m->id)
                        <form method="POST"
                            action="{{ route('teams.members.remove', [$team->id, $m->id]) }}"
                            style="display:inline"
                            onsubmit="return confirm('Remove {{ $m->name }} from team?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sa-btn"
                                style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">
                                Remove
                            </button>
                        </form>
                        @else
                        <span class="text-muted-xs">Team Lead</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted-xs" style="padding:32px">
                        No members in this team yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script>
function toggleAddMember() {
    var f = document.getElementById('add-member-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
