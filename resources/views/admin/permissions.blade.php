@extends('layouts.app')
@section('title', 'Permissions')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Permissions</h1>
            <p class="page-subtitle">Grant extra access to specific team members beyond their role</p>
        </div>
    </div>

    {{-- HOW IT WORKS --}}
    <div class="perm-how-it-works">
        <div class="perm-how-icon">💡</div>
        <div class="perm-how-content">
            <div class="perm-how-title">How permissions work</div>
            <div class="perm-how-desc">
                Every user gets permissions from their <strong>Role</strong> automatically.
                Use this page only when you need to give one specific person access to something
                extra — without changing their role.
                <strong>Example:</strong> Sarah is an Employee but needs to view reports →
                grant her "View Reports" here.
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="fv-alert fv-alert-error mb-md">✗ {{ session('error') }}</div>
    @endif

    {{-- SELECT MEMBER --}}
    <div class="perm-select-card">
        <div class="perm-select-label">Select a team member to manage</div>
        <form method="GET" action="{{ route('admin.permissions') }}" id="member-form">
            <div class="perm-select-row">
                <select name="user_id" class="perm-select-input"
                        onchange="document.getElementById('member-form').submit()">
                    <option value="">— Choose team member —</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}"
                        {{ isset($selectedUser) && $selectedUser->id === $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->getRoleNames()->first() ?? 'no role' }})
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if(isset($selectedUser))

    {{-- USER INFO --}}
    <div class="perm-user-card">
        <div class="perm-user-avatar">
            {{ strtoupper(substr($selectedUser->name, 0, 2)) }}
        </div>
        <div class="perm-user-info">
            <div class="perm-user-name">{{ $selectedUser->name }}</div>
            <div class="perm-user-email">{{ $selectedUser->email }}</div>
        </div>
        <div class="perm-user-role">
            <span class="perm-role-badge">
                {{ ucfirst(str_replace('_', ' ', $selectedUser->getRoleNames()->first() ?? 'no role')) }}
            </span>
            <div class="perm-role-note">Current role</div>
        </div>
    </div>

    {{-- PERMISSIONS BY FEATURE --}}
    @php
    $directPerms = $selectedUser->getDirectPermissions()->pluck('name')->toArray();
    $rolePerms   = $selectedUser->getPermissionsViaRoles()->pluck('name')->toArray();

    $featureGroups = [
        'Team & Users' => [
            'view_team'              => 'View team members',
            'invite_members'         => 'Invite new members',
            'remove_members'         => 'Remove members',
            'update_member_roles'    => 'Change member roles',
            'manage_employee_status' => 'Suspend / activate users',
        ],
        'Projects & Tasks' => [
            'view_projects'   => 'View projects',
            'create_projects' => 'Create projects',
            'edit_projects'   => 'Edit projects',
            'delete_projects' => 'Delete projects',
        ],
        'Performance & Reports' => [
            'view_reports'         => 'View team reports',
            'view_fairness'        => 'View fairness engine',
            'run_fairness_analysis'=> 'Run fairness analysis',
            'view_command_center'  => 'Access Command Center',
        ],
        'AI & Intelligence' => [
            'view_ai'           => 'View AI Intel',
            'query_ai'          => 'Use AI agent (Outy)',
            'refresh_ai_summary'=> 'Refresh AI summary',
        ],
        'Blockers' => [
            'view_blockers'    => 'View blockers',
            'create_blockers'  => 'Create blockers',
            'resolve_blockers' => 'Resolve blockers',
            'escalate_blockers'=> 'Escalate blockers',
        ],
        'Settings & Admin' => [
            'view_settings'          => 'View settings',
            'edit_org_settings'      => 'Edit org settings',
            'manage_github_settings' => 'Manage GitHub',
            'manage_billing'         => 'Manage billing',
            'manage_modules'         => 'Toggle modules',
        ],
    ];
    @endphp

    <div class="perm-groups">
        @foreach($featureGroups as $groupName => $perms)
        <div class="perm-group-card">
            <div class="perm-group-title">{{ $groupName }}</div>
            <div class="perm-items">
                @foreach($perms as $permKey => $permLabel)
                @php
                    $hasViaRole  = in_array($permKey, $rolePerms);
                    $hasDirectly = in_array($permKey, $directPerms);
                @endphp
                <div class="perm-item">
                    <div class="perm-item-left">
                        <div class="perm-item-label">{{ $permLabel }}</div>
                        <div class="perm-item-source">
                            @if($hasViaRole)
                            <span class="perm-source-role">✓ From role</span>
                            @elseif($hasDirectly)
                            <span class="perm-source-direct">✓ Granted directly</span>
                            @else
                            <span class="perm-source-none">No access</span>
                            @endif
                        </div>
                    </div>
                    <div class="perm-item-right">
                        @if($hasViaRole)
                        <span class="perm-locked">🔒 Role default</span>
                        @elseif($hasDirectly)
                        <form method="POST"
                              action="{{ route('admin.permissions.revoke', $selectedUser->id) }}"
                              style="display:inline">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="permission" value="{{ $permKey }}">
                            <button type="submit" class="perm-revoke-btn"
                                    onclick="return confirm('Remove this permission?')">
                                Revoke
                            </button>
                        </form>
                        @else
                        <form method="POST"
                              action="{{ route('admin.permissions.grant', $selectedUser->id) }}"
                              style="display:inline">
                            @csrf
                            <input type="hidden" name="permission" value="{{ $permKey }}">
                            <button type="submit" class="perm-grant-btn">+ Grant</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    @else

    <div class="perm-empty-state">
        <div class="perm-empty-icon">👆</div>
        <div class="perm-empty-title">Select a team member above</div>
        <div class="perm-empty-desc">Choose a person to view and manage their extra permissions</div>
    </div>

    @endif

</div>
@endsection
