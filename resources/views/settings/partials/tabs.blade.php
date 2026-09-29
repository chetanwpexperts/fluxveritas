@php
$pendingCount = \App\Models\User::where('organization_id', auth()->user()->organization_id)
    ->where('onboarding_status', 'pending')
    ->count();
$isAdmin = auth()->user()->hasAnyRole(['admin', 'owner', 'ceo']);
$isSA    = auth()->user()->hasRole('super_admin');
@endphp

<div class="stng-header">
    <div class="stng-header-left">
        <a href="{{ route('dashboard') }}" class="stng-back-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
            Back to Dashboard
        </a>
        <h1 class="stng-title">Settings</h1>
        <p class="stng-subtitle">Manage your account and organization</p>
    </div>
</div>

<div class="stng-tabs">
    <a href="{{ route('settings.profile') }}"
       class="stng-tab {{ request()->routeIs('settings.profile*') ? 'stng-tab-active' : '' }}">
        👤 Profile
    </a>
    <a href="{{ route('settings.notifications') }}"
       class="stng-tab {{ request()->routeIs('settings.notifications*') ? 'stng-tab-active' : '' }}">
        🔔 Notifications
    </a>
    <a href="{{ route('settings.github') }}"
       class="stng-tab {{ request()->routeIs('settings.github*') ? 'stng-tab-active' : '' }}">
        ⚡ GitHub
    </a>

    @if($isAdmin || $isSA)
    <a href="{{ route('settings.organization') }}"
       class="stng-tab {{ request()->routeIs('settings.organization*') ? 'stng-tab-active' : '' }}">
        🏢 Organization
    </a>
    <a href="{{ route('admin.users') }}"
       class="stng-tab {{ request()->routeIs('admin.users*') || request()->routeIs('admin.create*') || request()->routeIs('admin.edit*') ? 'stng-tab-active' : '' }}">
        👥 Users
    </a>
    <a href="{{ route('admin.roles') }}"
       class="stng-tab {{ request()->routeIs('admin.roles*') ? 'stng-tab-active' : '' }}">
        🔑 Roles
    </a>
    <a href="{{ route('admin.permissions') }}"
       class="stng-tab {{ request()->routeIs('admin.permissions*') ? 'stng-tab-active' : '' }}">
        🛡️ Permissions
    </a>
    <a href="{{ route('admin.designations') }}"
       class="stng-tab {{ request()->routeIs('admin.designations*') ? 'stng-tab-active' : '' }}">
        🏷️ Designations
    </a>
    @if(Route::has('admin.modules'))
    <a href="{{ route('admin.modules') }}"
       class="stng-tab {{ request()->routeIs('admin.modules*') ? 'stng-tab-active' : '' }}">
        🧩 Modules
    </a>
    @endif
    <a href="{{ route('admin.pending') }}"
       class="stng-tab {{ request()->routeIs('admin.pending*') ? 'stng-tab-active' : '' }}">
        ⏳ Pending
        @if($pendingCount > 0)
        <span class="stng-tab-badge">{{ $pendingCount }}</span>
        @endif
    </a>
    @endif

    @if($isSA)
    <a href="{{ route('settings.security') }}"
       class="stng-tab {{ request()->routeIs('settings.security*') ? 'stng-tab-active' : '' }}">
        🔒 Security
    </a>
    <a href="{{ route('settings.audit') }}"
       class="stng-tab {{ request()->routeIs('settings.audit*') ? 'stng-tab-active' : '' }}">
        📋 Audit
    </a>
    @endif
</div>
<div class="stng-tabs-divider"></div>
