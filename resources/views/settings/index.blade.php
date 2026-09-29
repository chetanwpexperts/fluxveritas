@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div class="stng-cards-grid">

        {{-- PERSONAL --}}
        <div class="stng-group">
            <div class="stng-group-label">Personal</div>
            <div class="stng-cards">

                <a href="{{ route('settings.profile') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-blue">👤</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Profile</div>
                        <div class="stng-card-desc">Update your name, email, phone and avatar</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('settings.notifications') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-amber">🔔</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Notifications</div>
                        <div class="stng-card-desc">Control which emails and alerts you receive</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('settings.github') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-dark">⚡</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">GitHub</div>
                        <div class="stng-card-desc">Connect GitHub account and sync repositories</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

            </div>
        </div>

        {{-- ORGANIZATION --}}
        @canany(['edit_org_settings', 'manage_roles'])
        <div class="stng-group">
            <div class="stng-group-label">Organization</div>
            <div class="stng-cards">

                <a href="{{ route('settings.organization') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-green">🏢</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Organization</div>
                        <div class="stng-card-desc">Name, logo, timezone and danger zone</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('admin.users') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-blue">👥</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Users</div>
                        <div class="stng-card-desc">Manage team members, invite and remove users</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('admin.roles') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-purple">🔑</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Roles</div>
                        <div class="stng-card-desc">View and manage roles defined in the system</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('admin.permissions') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-red">🛡️</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Permissions</div>
                        <div class="stng-card-desc">Grant or revoke specific permissions per user</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('admin.designations') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-amber">🏷️</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Designations</div>
                        <div class="stng-card-desc">Manage job titles and seniority levels</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                @if(Route::has('admin.modules'))
                <a href="{{ route('admin.modules') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-green">🧩</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Modules</div>
                        <div class="stng-card-desc">Toggle features on or off for your organization</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>
                @endif

                <a href="{{ route('admin.pending') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-amber">⏳</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Pending Approvals</div>
                        <div class="stng-card-desc">Review pending user registration requests</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

            </div>
        </div>
        @endcanany

        {{-- PLATFORM (SA only) --}}
        @if(auth()->user()->hasRole('super_admin'))
        <div class="stng-group">
            <div class="stng-group-label">Platform</div>
            <div class="stng-cards">

                <a href="{{ route('settings.platform') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-purple">⚡</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">
                            Platform Settings
                            <span class="stng-sa-badge">SA</span>
                        </div>
                        <div class="stng-card-desc">Platform-wide configuration</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('settings.ai') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-green">🤖</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">AI Configuration</div>
                        <div class="stng-card-desc">AI provider status and plan mapping</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('settings.security') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-red">🔒</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Security</div>
                        <div class="stng-card-desc">Security checks and session info</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

                <a href="{{ route('settings.audit') }}" class="stng-card">
                    <div class="stng-card-icon stng-icon-dark">📋</div>
                    <div class="stng-card-body">
                        <div class="stng-card-title">Audit Logs</div>
                        <div class="stng-card-desc">Platform activity and access logs</div>
                    </div>
                    <div class="stng-card-arrow">→</div>
                </a>

            </div>
        </div>
        @endif

        {{-- BILLING --}}
        <div class="stng-group">
            <div class="stng-group-label">Billing</div>
            <div class="stng-plan-card">
                <div class="stng-plan-left">
                    <div class="stng-plan-label">Current Plan</div>
                    <div class="stng-plan-name">
                        {{ ucfirst(auth()->user()->organization?->plan ?? 'Free') }}
                    </div>
                    <div class="stng-plan-desc">
                        @if((auth()->user()->organization?->plan ?? 'free') === 'free')
                            Upgrade to unlock AI scoring, bias detection and more
                        @else
                            All features unlocked
                        @endif
                    </div>
                </div>
                @if(Route::has('billing.index'))
                <a href="{{ route('billing.index') }}" class="stng-upgrade-btn">Manage Plan →</a>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
