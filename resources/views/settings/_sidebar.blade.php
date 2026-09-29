@php
$currentRoute = request()->route()->getName();
$org = auth()->user()->organization;
$user = auth()->user();
$isSuperAdmin = $user->hasRole('super_admin');
$isOwnerOrAdmin = $user->hasAnyRole(['owner', 'admin']);
@endphp

<div class="settings-sidebar-wrap">
    <nav class="settings-nav">

        {{-- COMMON SETTINGS (all roles) --}}
        <div class="settings-nav-section">Account</div>

        @foreach([
            ['route' => 'settings.profile',       'label' => 'Profile'],
            ['route' => 'settings.notifications',  'label' => 'Notifications'],
            ['route' => 'settings.github',         'label' => 'GitHub'],
        ] as $item)
        @php $active = $currentRoute === $item['route']; @endphp
        <a href="{{ route($item['route']) }}" class="settings-nav-link{{ $active ? ' active' : '' }}">
            {{ $item['label'] }}
        </a>
        @endforeach

        {{-- ORG OWNER/ADMIN SETTINGS --}}
        @if($isOwnerOrAdmin)
        <div class="settings-nav-section bordered-top">Organization</div>

        @foreach([
            ['route' => 'settings.organization', 'label' => 'General'],
            ['route' => 'admin.modules',         'label' => 'Modules'],
        ] as $item)
        @php $active = $currentRoute === $item['route']; @endphp
        <a href="{{ route($item['route']) }}" class="settings-nav-link{{ $active ? ' active' : '' }}">
            {{ $item['label'] }}
        </a>
        @endforeach
        @endif

        {{-- SUPER ADMIN SETTINGS --}}
        @if($isSuperAdmin)
        <div class="settings-nav-section bordered-top">Platform</div>

        @foreach([
            ['route' => 'settings.platform', 'label' => 'Platform Settings'],
            ['route' => 'settings.ai',       'label' => 'AI Configuration'],
            ['route' => 'settings.security', 'label' => 'Security'],
            ['route' => 'settings.audit',    'label' => 'Audit Logs'],
        ] as $item)
        @php $active = $currentRoute === $item['route']; @endphp
        <a href="{{ route($item['route']) }}" class="settings-nav-link{{ $active ? ' active' : '' }}">
            {{ $item['label'] }}
            @if($item['label'] === 'Platform Settings')
            <span class="settings-badge-sa">SA</span>
            @endif
        </a>
        @endforeach
        @endif

    </nav>

    {{-- Plan card for non-super-admin --}}
    @if(!$isSuperAdmin && $org)
    <div class="settings-plan-card">
        <div class="settings-plan-label">Current Plan</div>
        <div class="settings-plan-value">{{ ucfirst($org->plan ?? 'free') }}</div>
        @if(($org->plan ?? 'free') !== 'enterprise')
        <a href="#" class="settings-upgrade-btn">Upgrade Plan</a>
        @endif
    </div>
    @endif

    {{-- Super admin badge --}}
    @if($isSuperAdmin)
    <div class="settings-sa-badge">
        <div class="settings-sa-badge-label">Logged in as</div>
        <div class="settings-sa-badge-value">⚡ Super Admin</div>
        <a href="{{ route('superadmin.index') }}" class="settings-sa-badge-link">Platform Panel →</a>
    </div>
    @endif
</div>
