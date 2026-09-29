<x-app-layout>
@section('title', 'Team Members')

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Team Members</h1>
        <p class="page-subtitle">{{ $members->count() }} {{ Str::plural('member', $members->count()) }} in your organization</p>
    </div>
    @can('invite_members')
    <div class="page-header-right">
        <a href="{{ route('team.invite') }}" class="btn-primary">+ Invite Member</a>
    </div>
    @endcan
</div>

        {{-- Add People panel --}}
        @hasanyrole('owner|admin|super_admin')
        <div style="display:flex;gap:10px;margin-bottom:1.5rem;
                    padding:1rem;background:#f9fafb;border:0.5px solid #f3f4f6;
                    border-radius:12px">
            <div style="flex:1">
                <div style="font-size:14px;font-weight:500;color:#18181b">Add People</div>
                <div style="font-size:12px;color:#6b7280">
                    Invite individually, or import many at once from a file.
                </div>
            </div>
            <a href="{{ route('team.bulk-invite') }}"
               style="background:#f3f4f6;color:#18181b;border:0.5px solid #e5e7eb;
                      padding:8px 16px;border-radius:8px;font-size:13px;
                      text-decoration:none;white-space:nowrap;align-self:center">
                Invite by email
            </a>
            <a href="{{ route('import.employees') }}"
               style="background:#18181b;color:#fff;border:none;
                      padding:8px 16px;border-radius:8px;font-size:13px;
                      text-decoration:none;white-space:nowrap;align-self:center">
                Import from file
            </a>
        </div>
        @endhasanyrole

        {{-- Alerts --}}
        @if(session('success'))
        <div class="fv-alert fv-alert-success anim-fade-up">
            <svg style="width:16px; height:16px; flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="fv-alert fv-alert-error anim-fade-up">
            <svg style="width:16px; height:16px; flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        {{-- My Direct Reports --}}
        @if(isset($myDirectReports) && $myDirectReports->count() > 0)
        <div class="team-section">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
                <h3 style="font-size:14px;font-weight:700;color:#18181b;margin:0">My Direct Reports</h3>
                <span style="display:inline-flex;align-items:center;justify-content:center;
                    min-width:22px;height:22px;background:#18181b;color:#fff;
                    border-radius:999px;font-size:11px;font-weight:700;padding:0 6px">
                    {{ $myDirectReports->count() }}
                </span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:24px">
                @foreach($myDirectReports as $report)
                <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                    background:#fff;border:1px solid #e4e4e7;border-radius:12px">
                    <div style="width:40px;height:40px;border-radius:50%;background:#18181b;
                        color:#fff;display:flex;align-items:center;justify-content:center;
                        font-size:15px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($report->name, 0, 1)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:700;color:#18181b;margin-bottom:2px;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $report->name }}
                        </div>
                        <div style="font-size:12px;color:#71717a;margin-bottom:5px">
                            {{ $report->job_title ?? $report->designation ?? 'Team Member' }}
                        </div>
                        @if($report->workLogs->count() > 0)
                        <span style="font-size:11px;font-weight:600;color:#16a34a;background:#f0fdf4;
                            padding:2px 8px;border-radius:999px;border:1px solid #bbf7d0">
                            ✅ Logged today
                        </span>
                        @else
                        <span style="font-size:11px;font-weight:600;color:#dc2626;background:#fef2f2;
                            padding:2px 8px;border-radius:999px;border:1px solid #fecaca">
                            ❌ Not logged
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Team members table --}}
        <div class="fv-card anim-fade-up">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">All Members</div>
                    <div class="fv-section-sub">Everyone currently in your organization.</div>
                </div>
            </div>

            @if($members->isEmpty())
            <div class="fv-empty">
                <div style="width:56px; height:56px; background:var(--surface); border-radius:16px; border:1px solid var(--border);
                            display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg style="width:28px; height:28px; color:var(--text-4);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="fv-empty-title">No team members yet</div>
                <div class="fv-empty-desc">Invite your first team member to start tracking their contributions.</div>
                @can('invite_members')
                <a href="{{ route('team.invite') }}" class="fv-btn fv-btn-primary">Invite Member</a>
                @endcan
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Role &amp; Level</th>
                            <th>Manager</th>
                            <th>GitHub</th>
                            <th>Commits</th>
                            <th>PRs</th>
                            <th>Last Active</th>
                            <th style="text-align:right; padding-right:20px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="team-members-list">
                        @foreach($members as $i => $member)
                        <tr class="anim-fade-up delay-{{ min($i+1,4) }}">
                            <td>
                                <div class="au-user-cell">
                                    <div class="au-avatar">{{ strtoupper(substr($member->name, 0, 2)) }}</div>
                                    <div>
                                        <div class="au-name">
                                            {{ $member->name }}
                                            @if($member->id === auth()->id())
                                                <span class="au-self-tag">(you)</span>
                                            @endif
                                        </div>
                                        <div class="au-email">{{ $member->email }}</div>
                                        @if($member->job_title)
                                        <div class="au-job-title">{{ $member->job_title }}</div>
                                        @endif
                                        @if(!empty($member->skills))
                                        <div class="au-skills-row">
                                            @foreach(array_slice($member->skills, 0, 3) as $skill)
                                            <span class="au-skill-tag">{{ $skill }}</span>
                                            @endforeach
                                            @if(count($member->skills) > 3)
                                            <span class="au-skill-more">+{{ count($member->skills) - 3 }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="au-role-cell">
                                    @forelse($member->roles->unique('name') as $spRole)
                                        @if($spRole->name === 'owner')
                                            <span class="fv-badge au-role-badge" style="background:#18181b;color:white;border-color:#18181b;">Owner</span>
                                        @else
                                            <span class="fv-badge fv-badge-gray au-role-badge">{{ ucwords(str_replace('_', ' ', $spRole->name)) }}</span>
                                        @endif
                                    @empty
                                        <span class="fv-badge fv-badge-gray au-role-badge">{{ ucwords($member->role ?? 'Employee') }}</span>
                                    @endforelse
                                    @if($member->seniority_level)
                                    <span class="au-seniority-badge au-seniority-{{ $member->seniority_level }}">
                                        {{ ucfirst(str_replace('_', ' ', $member->seniority_level)) }}
                                    </span>
                                    @endif
                                    @if($member->employment_type)
                                    <span class="au-employment-badge au-emp-{{ $member->employment_type }}">
                                        {{ ucfirst(str_replace('_', ' ', $member->employment_type)) }}
                                    </span>
                                    @endif
                                    @if($member->work_location)
                                    <span class="au-location-tag au-location-{{ $member->work_location }}">
                                        {{ ucfirst($member->work_location) }}
                                    </span>
                                    @endif

                                    {{-- Role change --}}
                                    @can('update_member_roles')
                                    @if($member->id !== auth()->id())
                                    <form method="POST" action="{{ route('team.updateRole', $member->id) }}" class="au-role-form">
                                        @csrf @method('PATCH')
                                        <select name="role" class="au-role-select" data-auto-submit>
                                            <option value="employee" {{ $member->role === 'employee' ? 'selected' : '' }}>Employee</option>
                                            <option value="admin"    {{ $member->role === 'admin'    ? 'selected' : '' }}>Admin</option>
                                            <option value="owner"    {{ $member->role === 'owner'    ? 'selected' : '' }}>Owner</option>
                                        </select>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                            <td class="au-manager-cell">
                                {{ $member->reportingManager?->name ?? '—' }}
                            </td>
                            <td>
                                @if($member->github_username)
                                    <span style="font-family:monospace; font-size:0.8rem; color:var(--text-2);">{{ $member->github_username }}</span>
                                @else
                                    <span style="font-size:0.8rem; color:#cbd5e1;">Not set</span>
                                @endif
                            </td>
                            <td style="font-variant-numeric:tabular-nums; font-weight:600; color:var(--text);">
                                {{ number_format($member->stat_commits) }}
                            </td>
                            <td style="font-variant-numeric:tabular-nums; font-weight:600; color:var(--text);">
                                {{ number_format($member->stat_prs) }}
                            </td>
                            <td style="white-space:nowrap; color:#94a3b8; font-size:0.8rem;">
                                @if($member->last_active)
                                    {{ \Carbon\Carbon::parse($member->last_active)->diffForHumans() }}
                                @else
                                    <span style="color:#cbd5e1;">Never</span>
                                @endif
                            </td>
                            <td style="text-align:right; padding-right:20px; white-space:nowrap;">
                                @can('remove_members')
                                @if($member->id !== auth()->id())
                                <form method="POST" action="{{ route('team.remove', $member->id) }}"
                                      data-confirm="Remove {{ $member->name }} from the team?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="fv-btn fv-btn-danger"
                                            style="font-size:0.78rem; padding:5px 12px;">
                                        Remove
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="team-members-pagination" class="pagination-wrapper"></div>
            @endif
        </div>

        {{-- Pending Invitations --}}
        @if($pendingInvitations->isNotEmpty())
        <div class="fv-card anim-fade-up delay-2">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Pending Invitations</div>
                    <div class="fv-section-sub">{{ $pendingInvitations->count() }} awaiting acceptance</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Expires</th>
                            <th>Invite Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingInvitations as $invitation)
                        <tr>
                            <td style="font-weight:500; color:#0f172a;">{{ $invitation->email }}</td>
                            <td>
                                @if($invitation->role === 'admin')
                                    <span class="fv-badge fv-badge-gray">Admin</span>
                                @else
                                    <span class="fv-badge fv-badge-gray">Employee</span>
                                @endif
                            </td>
                            <td style="font-size:0.8rem; color:#94a3b8; white-space:nowrap;">
                                {{ $invitation->expires_at->format('M j, Y') }}
                            </td>
                            <td>
                                @php $link = route('team.accept', $invitation->token); @endphp
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="text" value="{{ $link }}" readonly
                                           style="font-size:0.75rem; font-family:monospace; padding:5px 10px;
                                                  border:1px solid var(--border); border-radius:6px; background:var(--surface);
                                                  color:var(--text-2); width:260px; outline:none;"
                                           onclick="this.select()">
                                    <button onclick="navigator.clipboard.writeText('{{ $link }}'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500)"
                                            class="fv-btn fv-btn-secondary"
                                            style="font-size:0.78rem; padding:5px 12px; white-space:nowrap;">
                                        Copy
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

</div>
</x-app-layout>

@push('scripts')
<script src="{{ asset('js/team-page.js') }}"></script>
<script src="{{ asset('js/admin-users.js') }}"></script>
@endpush
