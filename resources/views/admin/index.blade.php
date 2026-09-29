<x-app-layout>
@section('title', 'Admin Panel')

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Admin Panel</h1>
        <p class="page-subtitle">Manage users, roles and permissions for your organization</p>
    </div>
</div>

        {{-- Alerts --}}
        @if(session('success'))
        <div class="fv-alert fv-alert-success anim-fade-up">
            <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="fv-alert fv-alert-error anim-fade-up">
            <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        {{-- Stats Row --}}
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;" class="anim-fade-up">
            <div class="fv-card" style="padding:24px 28px;">
                <div style="font-size:0.75rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">Team Members</div>
                <div style="font-size:2.25rem; font-weight:800; color:var(--text); line-height:1;">{{ $stats['total_users'] }}</div>
                <div style="font-size:0.8rem; color:var(--text-3); margin-top:6px;">in your organization</div>
            </div>
            <div class="fv-card" style="padding:24px 28px;">
                <div style="font-size:0.75rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">Roles</div>
                <div style="font-size:2.25rem; font-weight:800; color:var(--text); line-height:1;">{{ $stats['total_roles'] }}</div>
                <div style="font-size:0.8rem; color:var(--text-3); margin-top:6px;">defined in system</div>
            </div>
            <div class="fv-card" style="padding:24px 28px;">
                <div style="font-size:0.75rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">Permissions</div>
                <div style="font-size:2.25rem; font-weight:800; color:var(--text); line-height:1;">{{ $stats['total_permissions'] }}</div>
                <div style="font-size:0.8rem; color:var(--text-3); margin-top:6px;">granular capabilities</div>
            </div>
        </div>

        {{-- Quick Navigation Cards --}}
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:16px;" class="anim-fade-up delay-1">
            <div class="fv-card" style="padding:28px; display:flex; flex-direction:column; gap:16px;">
                <div style="width:44px; height:44px; background:var(--surface); border:1px solid var(--border); border-radius:12px;
                            display:flex; align-items:center; justify-content:center;">
                    <svg style="width:22px; height:22px; color:var(--text-2);" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:1rem; font-weight:700; color:var(--text); margin-bottom:6px;">User Management</div>
                    <div style="font-size:0.8125rem; color:var(--text-3); line-height:1.5;">Manage team members, assign roles, activate or deactivate accounts.</div>
                </div>
                <a href="{{ route('admin.users') }}" class="fv-btn fv-btn-primary" style="align-self:flex-start;">
                    Manage Users
                </a>
            </div>

            <div class="fv-card" style="padding:28px; display:flex; flex-direction:column; gap:16px;">
                <div style="width:44px; height:44px; background:var(--surface); border:1px solid var(--border); border-radius:12px;
                            display:flex; align-items:center; justify-content:center;">
                    <svg style="width:22px; height:22px; color:var(--text-2);" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:1rem; font-weight:700; color:var(--text); margin-bottom:6px;">Role Management</div>
                    <div style="font-size:0.8125rem; color:var(--text-3); line-height:1.5;">View all roles and their associated permissions across the system.</div>
                </div>
                <a href="{{ route('admin.roles') }}" class="fv-btn fv-btn-secondary" style="align-self:flex-start;">
                    View Roles
                </a>
            </div>

            <div class="fv-card" style="padding:28px; display:flex; flex-direction:column; gap:16px;">
                <div style="width:44px; height:44px; background:var(--surface); border:1px solid var(--border); border-radius:12px;
                            display:flex; align-items:center; justify-content:center;">
                    <svg style="width:22px; height:22px; color:var(--text-2);" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:1rem; font-weight:700; color:var(--text); margin-bottom:6px;">Permission Management</div>
                    <div style="font-size:0.8125rem; color:var(--text-3); line-height:1.5;">Grant or revoke specific permissions for individual users beyond their role.</div>
                </div>
                <a href="{{ route('admin.permissions') }}" class="fv-btn fv-btn-secondary" style="align-self:flex-start;">
                    Manage Permissions
                </a>
            </div>

            <div class="fv-card" style="padding:28px; display:flex; flex-direction:column; gap:16px; position:relative;">
                @if($pendingCount > 0)
                <span style="position:absolute; top:16px; right:16px; font-size:0.7rem; font-weight:800;
                             padding:3px 9px; border-radius:20px; background:#fef2f2; color:#dc2626;
                             border:1px solid rgba(220,38,38,0.2);">
                    {{ $pendingCount }}
                </span>
                @endif
                <div style="width:44px; height:44px; background:var(--surface); border:1px solid var(--border); border-radius:12px;
                            display:flex; align-items:center; justify-content:center;">
                    <svg style="width:22px; height:22px; color:{{ $pendingCount > 0 ? 'var(--danger)' : 'var(--text-4)' }};" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:1rem; font-weight:700; color:var(--text); margin-bottom:6px;">Pending Approvals</div>
                    <div style="font-size:0.8125rem; color:var(--text-3); line-height:1.5;">
                        @if($pendingCount > 0)
                            <span style="color:#dc2626; font-weight:600;">{{ $pendingCount }} user{{ $pendingCount > 1 ? 's' : '' }}</span> waiting for review.
                        @else
                            No pending user registrations at this time.
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.pending') }}" class="fv-btn {{ $pendingCount > 0 ? 'fv-btn-danger' : 'fv-btn-secondary' }}" style="align-self:flex-start;">
                    Review Pending
                </a>
            </div>

            <div class="fv-card" style="padding:28px; display:flex; flex-direction:column; gap:16px;">
                <div style="width:44px; height:44px; background:var(--surface); border:1px solid var(--border); border-radius:12px;
                            display:flex; align-items:center; justify-content:center;">
                    <svg style="width:22px; height:22px; color:var(--text-2);" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:1rem; font-weight:700; color:var(--text); margin-bottom:6px;">Module Management</div>
                    <div style="font-size:0.8125rem; color:var(--text-3); line-height:1.5;">Toggle features on or off for your organization based on your plan.</div>
                </div>
                <a href="{{ route('admin.modules') }}" class="fv-btn fv-btn-secondary" style="align-self:flex-start;">
                    Manage Modules
                </a>
            </div>
        </div>

        {{-- Recent Users --}}
        <div class="fv-card anim-fade-up delay-2">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Recent Members</div>
                    <div class="fv-section-sub">Latest 5 users to join your organization.</div>
                </div>
                <a href="{{ route('admin.users') }}" class="fv-btn fv-btn-secondary" style="font-size:0.8rem; padding:7px 14px;">
                    View All
                </a>
            </div>

            @if($recentUsers->isEmpty())
            <div class="fv-empty">
                <div class="fv-empty-title">No users yet</div>
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:34px; height:34px; border-radius:50%; background:var(--surface);
                                                border:1px solid var(--border); display:flex; align-items:center;
                                                justify-content:center; font-size:0.75rem; font-weight:800;
                                                color:var(--text-2); flex-shrink:0;">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600; color:var(--text); font-size:0.875rem;">{{ $user->name }}</div>
                                        <div style="font-size:0.75rem; color:var(--text-4);">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @php $spRoles = $user->getRoleNames()->unique(); @endphp
                                    @if($spRoles->isNotEmpty())
                                        @foreach($spRoles as $roleName)
                                            <span class="fv-badge fv-badge-gray" style="font-size:0.7rem;">
                                                {{ ucwords(str_replace('_', ' ', $roleName)) }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="fv-badge fv-badge-gray" style="font-size:0.7rem;">
                                            {{ ucwords(str_replace('_', ' ', $user->role ?? 'employee')) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($user->is_active !== false)
                                    <span class="fv-badge fv-badge-green">Active</span>
                                @else
                                    <span class="fv-badge" style="background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,0.15);">Inactive</span>
                                @endif
                            </td>
                            <td style="color:#64748b; font-size:0.8125rem; white-space:nowrap;">
                                {{ $user->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

</div>
</x-app-layout>
