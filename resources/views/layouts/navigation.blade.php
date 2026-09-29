{{-- Super Admin Bar --}}
@if(auth()->user()->hasRole('super_admin'))
<div class="sa-bar">
    <span class="sa-bar-label">⚡ Super Admin Mode</span>
    <div class="sa-bar-controls">
        <form id="org-switch-form" method="POST" action="" style="display:none;">@csrf</form>
        {{-- dynamic style: JS-controlled form, display:none required --}}
        <select id="org-switcher" name="org_id" class="sa-org-switcher"
                onchange="if(this.value){document.getElementById('org-switch-form').action='/superadmin/switch/'+this.value;document.getElementById('org-switch-form').submit();}">
            <option value="">Switch Organization…</option>
            @foreach(App\Models\Organization::orderBy('name')->get() as $org)
            <option value="{{ $org->id }}" {{ auth()->user()->organization_id == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
            @endforeach
        </select>
        <a href="{{ route('superadmin.index') }}" class="sa-platform-link">Platform →</a>
    </div>
</div>
@endif

@php
$navModuleService = new \App\Services\ModuleService();
$navOrgId = auth()->user()->organization_id;
$navIsSa  = auth()->user()->hasRole('super_admin');
$navHas   = fn(string $m) => $navIsSa || ($navOrgId && $navModuleService->hasModule($navOrgId, $m));
@endphp

{{-- ─── TOP BAR ──────────────────────────────────────────────────────────────── --}}
<nav x-data="{ userOpen: false }" id="fv-topbar" class="topbar">

    {{-- Sidebar toggle --}}
    <button onclick="toggleSidebar()" title="Toggle sidebar" class="topbar-toggle-btn">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Logo --}}
    <a href="{{ route('dashboard') }}" class="topbar-logo">
        <div class="topbar-logo-mark">OQ</div>
        <span id="fv-logo-text" class="topbar-logo-text">OutraqHQ</span>
    </a>

    <div class="topbar-spacer"></div>

    {{-- Global Search --}}
    @auth
    <div id="global-search-wrap" style="position:relative;max-width:320px;width:100%">
        <input type="text" id="globalSearch"
               placeholder="Search employees, docs, projects..."
               autocomplete="off"
               style="width:100%;padding:7px 12px 7px 34px;border:0.5px solid #e5e7eb;border-radius:8px;font-size:13px;background:#f9fafb;color:#18181b;outline:none">
        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;"
             width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <div id="searchResults"
             style="display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:0.5px solid #e5e7eb;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.08);z-index:9999;max-height:400px;overflow-y:auto">
        </div>
    </div>
    @endauth

    {{-- Notification Bell --}}
    <div class="notif-wrapper" id="notif-wrapper">
        <button onclick="toggleNotifications()" id="notif-btn" class="notif-btn">
            <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="#71717a" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span id="notif-badge" class="notif-badge">0</span>
        </button>
        <div id="notif-dropdown" class="notif-dropdown">
            <div class="notif-header">
                <div class="notif-title">Notifications</div>
                <button onclick="markAllRead()" class="notif-mark-all-btn">Mark all read</button>
            </div>
            <div id="notif-list" class="notif-list">
                <div class="notif-loading">Loading...</div>
            </div>
            <div class="notif-footer">
                <a href="/notifications" class="notif-view-all">View all notifications →</a>
            </div>
        </div>
    </div>

    {{-- User Menu --}}
    <div class="user-menu">
        <button @click="userOpen = !userOpen" @click.away="userOpen = false" class="user-menu-btn">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
            <div class="user-info hidden sm:block">
                <div class="user-name">{{ Auth::user()->name }}</div>
                <div class="user-role-label">
                    {{ str_replace('_',' ', Auth::user()->getRoleNames()->first() ?? Auth::user()->role ?? 'user') }}
                </div>
            </div>
            <svg :class="{'rotate-180': userOpen}" class="user-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="userOpen"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="user-dropdown">
            <div class="user-dropdown-inner">
                <div class="user-profile-section">
                    <div class="user-profile-name">{{ Auth::user()->name }}</div>
                    <div class="user-profile-email">{{ Auth::user()->email }}</div>
                </div>
                @foreach([
                    [route('settings.index'), 'Settings'],
                    [route('profile.edit'),   'Profile'],
                    [route('profile.github'), 'GitHub Username'],
                ] as [$url, $label])
                <a href="{{ $url }}" class="user-menu-link">{{ $label }}</a>
                @endforeach
                @if(auth()->user()->hasRole('super_admin'))
                <a href="{{ route('superadmin.index') }}" class="user-menu-link user-menu-link-sa">⚡ Platform Panel</a>
                @endif
                @can('manage_roles')
                <a href="{{ route('settings.index') }}" class="user-menu-link">Settings</a>
                @endcan
                <div class="user-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="user-logout-btn">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</nav>

@auth
<script>
(function() {
    var input   = document.getElementById('globalSearch');
    var results = document.getElementById('searchResults');
    if (!input || !results) return;
    var timer;

    input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = this.value.trim();
        if (q.length < 2) { results.style.display = 'none'; return; }
        timer = setTimeout(function() { doSearch(q); }, 250);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { results.style.display = 'none'; input.blur(); }
    });

    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('global-search-wrap');
        if (wrap && !wrap.contains(e.target)) results.style.display = 'none';
    });

    function doSearch(q) {
        fetch('/search?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) { renderResults(data.results, q); })
        .catch(function() {});
    }

    var typeColors  = { employee: '#185fa5', announcement: '#854f0b', document: '#3b6d11', project: '#534ab7', task: '#6b7280' };
    var typeLabels  = { employee: 'Employee', announcement: 'Announcement', document: 'Document', project: 'Project', task: 'Task' };
    var typeIcons   = {
        employee: '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        announcement: '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>',
        document: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        project: '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
        task: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'
    };

    function renderResults(items, q) {
        if (!items.length) {
            results.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#9ca3af;font-size:13px">No results for &ldquo;' + escHtml(q) + '&rdquo;</div>';
            results.style.display = 'block';
            return;
        }
        var html = '<div style="padding:6px 0">';
        var lastType = null;
        items.forEach(function(item) {
            if (item.type !== lastType) {
                html += '<div style="padding:6px 12px 4px;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em">' + escHtml((typeLabels[item.type] || item.type) + 's') + '</div>';
                lastType = item.type;
            }
            var color = typeColors[item.type] || '#6b7280';
            var icon  = typeIcons[item.type] || '';
            html += '<a href="' + escHtml(item.url) + '" style="display:flex;align-items:center;gap:10px;padding:8px 12px;text-decoration:none;color:inherit;" onmouseover="this.style.background=\'#f9fafb\'" onmouseout="this.style.background=\'transparent\'">' +
                '<div style="width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;background:' + color + '22;flex-shrink:0;">' +
                '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="' + color + '" stroke-width="2"><' + '!' + '--icon-->' + icon + '</svg></div>' +
                '<div style="min-width:0;">' +
                '<div style="font-size:13px;font-weight:500;color:#18181b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escHtml(item.title) + '</div>' +
                '<div style="font-size:11px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escHtml(item.subtitle) + '</div>' +
                '</div></a>';
        });
        html += '</div>';
        results.innerHTML = html;
        results.style.display = 'block';
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
@endauth

{{-- ─── SIDEBAR ──────────────────────────────────────────────────────────────── --}}
<div id="fv-sidebar"
     style="position:fixed;left:0;top:56px;bottom:0;width:220px;background:white;border-right:1px solid #e4e4e7;z-index:90;overflow-y:auto;overflow-x:hidden;transition:width 0.3s ease;scrollbar-width:thin;scrollbar-color:#e4e4e7 transparent;">
    {{-- dynamic style: JS reads/sets width and left; position:fixed + z-index are structural --}}
    <div class="sidebar-inner">

        @php
        $settingsActive          = request()->routeIs('settings.*')
                                   && !request()->routeIs('admin.*')
                                   && !request()->routeIs('increment.*');
        $adminActive             = request()->routeIs('admin.*');
        $incrementSettingsActive = request()->routeIs('increment.settings')
                                   || request()->routeIs('increment.save-policy');
        $incrementReviewsActive  = request()->routeIs('increment.*')
                                   && !request()->routeIs('increment.settings')
                                   && !request()->routeIs('increment.save-policy')
                                   && !request()->routeIs('increment.my');

        $sidebarItem = function(string $url, string $label, string $svgPath, string $routePattern, bool $show = true, ?bool $activeOverride = null) {
            if (!$show) return '';
            $active      = $activeOverride !== null ? $activeOverride : request()->routeIs($routePattern);
            $activeClass = $active ? ' active' : '';
            return '<a href="' . $url . '" title="' . $label . '" class="sidebar-item' . $activeClass . '">'
                . '<svg class="sidebar-item-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">' . $svgPath . '</svg>'
                . '<span class="sidebar-item-label">' . $label . '</span>'
                . '</a>';
        };
        $sidebarSection = function(string $label) {
            return '<div class="sidebar-section-label">' . $label . '</div>';
        };
        $sidebarDivider = '<div class="sidebar-divider"></div>';
        @endphp

        @if(!auth()->user()->hasRole('super_admin'))
        {{-- SECTION 1 — WORKSPACE --}}
        {!! $sidebarSection('Workspace') !!}
        @can('view_dashboard')
        {!! $sidebarItem(route('dashboard'), 'Dashboard', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>', 'dashboard') !!}
        @endcan
        @can('view_projects')
        {!! $sidebarItem(route('projects.index'), 'Projects', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>', 'projects.*') !!}
        @endcan

        {!! $sidebarDivider !!}

        {{-- SECTION 2 — PEOPLE & ORG --}}
        {!! $sidebarSection('People & Org') !!}
        {!! $sidebarItem(route('directory.index'), 'Directory', '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>', 'directory.*') !!}
        {!! $sidebarItem(route('teams.index'), 'Teams', '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>', 'teams.*') !!}
        @can('manage_organization')
        {!! $sidebarItem(route('departments.index'), 'Departments', '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>', 'departments.*') !!}
        @endcan
        @can('view_team')
        {!! $sidebarItem(route('org-chart.index'), 'Org Chart', '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>', 'org-chart*') !!}
        @endcan

        {!! $sidebarDivider !!}

        {{-- SECTION 3 — WORK TELEMETRY --}}
        {!! $sidebarSection('Work Telemetry') !!}
        @can('view_dashboard')
        @php
        $worklogRoute = auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'manager'])
            ? route('worklog.team')
            : route('worklog.index');
        @endphp
        {!! $sidebarItem($worklogRoute, 'Work Log', '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>', 'worklog.*') !!}
        @endcan
        {!! $sidebarItem(route('tasks.index'), 'Tasks', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>', 'tasks.*') !!}
        {!! $sidebarItem(route('sprints.index'), 'Sprints', '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>', 'sprints.*') !!}
        @can('view_blockers')
        @if($navHas('blockers'))
        {!! $sidebarItem(route('dependency.index'), 'Blockers', '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>', 'dependency.*') !!}
        @endif
        @endcan
        {!! $sidebarItem(route('timesheets.index'), 'Timesheets', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'timesheets.*') !!}
        {!! $sidebarItem(route('expenses.index'), 'Expenses', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>', 'expenses.*') !!}
        {!! $sidebarItem(route('assets.index'), 'Asset Vault', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>', 'assets.*') !!}
        @if(auth()->user()->hasAnyRole(['admin','owner','super_admin','hr']))
        {!! $sidebarItem(route('payroll.index'), 'Payroll', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659 1.171-.33c.75.45.87.46.63.78M7.125 7.8l-.879-.66-1.171.33c-.75-.45-.87-.46-.63-.78m12.75 2.358.879.66 1.171-.33c.75-.45.87-.46.63-.78M16.875 7.8l.879-.66 1.171.33c.75.45.87.46.63.78"/>', 'payroll.*') !!}
        @endif
        {!! $sidebarItem(route('leaves.index'), 'Leaves', '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>', 'leaves.*') !!}
        {!! $sidebarItem(route('documents.index'), 'Documents', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>', 'documents.*') !!}
        {!! $sidebarItem(route('announcements.index'), 'Announcements', '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>', 'announcements.*') !!}

        {!! $sidebarDivider !!}

        {{-- SECTION 4 — REPORTS & INTELLIGENCE --}}
        {!! $sidebarSection('Reports & Intel') !!}
        @if(!auth()->user()->hasAnyRole(['super_admin', 'admin', 'owner', 'ceo', 'manager']))
        @if($navHas('increment_calculator'))
        {!! $sidebarItem(route('increment.my'), 'My Increment', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659 1.171-.33c.75.45.87.46.63.78M7.125 7.8l-.879-.66-1.171.33c-.75-.45-.87-.46-.63-.78m12.75 2.358.879.66 1.171-.33c.75-.45.87-.46.63-.78M16.875 7.8l.879-.66 1.171.33c.75.45.87.46.63.78"/>', 'increment.my') !!}
        @endif
        {!! $sidebarItem(route('feedback.my'), 'My Feedback', '<path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>', 'feedback.my') !!}
        @if($navHas('reports'))
        {!! $sidebarItem(route('reports.my'), 'My Report', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>', 'reports.my') !!}
        @endif
        @endif

        @if(auth()->user()->hasAnyRole(['admin','owner','ceo','manager']))
        @if($navHas('reports'))
        {!! $sidebarItem(route('reports.team'), 'Team Reports', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>', 'reports.team') !!}
        @endif
        {!! $sidebarItem(route('feedback.index'), 'Team Feedback', '<path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>', 'feedback.index') !!}
        @endif

        @if(auth()->user()->hasAnyRole(['hr','admin','owner','super_admin']))
        @if($navHas('hr_reports'))
        {!! $sidebarItem(route('hr.reports'), 'HR Reports', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>', 'hr.reports') !!}
        @endif
        @endif

        @can('view_fairness')
        @if($navHas('fairness_engine'))
        {!! $sidebarItem(route('fairness.index'), 'Fairness', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>', 'fairness.*') !!}
        @endif
        @endcan

        @can('view_ai')
        @if($navHas('ai_intelligence'))
        {!! $sidebarItem(route('ai.dashboard'), 'AI Intel', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>', 'ai.*') !!}
        @endif
        @endcan

        {!! $sidebarDivider !!}

        {{-- SECTION 5 — ADMIN & SETUP --}}
        @if(auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']))
        {!! $sidebarSection('Admin & Setup') !!}
        @if(auth()->user()->hasAnyRole(['ceo', 'super_admin']))
        @if($navHas('command_center'))
        {!! $sidebarItem(route('ceo.index'), 'Command Center', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>', 'ceo.*') !!}
        @endif
        @endif
        {!! $sidebarItem(route('settings.index'), 'Settings', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>', 'settings.*', true, $settingsActive) !!}
        @if(auth()->user()->hasAnyRole(['owner', 'admin', 'super_admin']))
        {!! $sidebarItem(route('billing.index'), 'Billing', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>', 'billing*') !!}
        @endif
        @if(auth()->user()->hasRole(['owner','admin','super_admin']))
        @if($navHas('increment_calculator'))
        {!! $sidebarItem(route('increment.reviews'), 'Increment Reviews', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>', 'increment.reviews', true, $incrementReviewsActive) !!}
        @endif
        @endif
        @if(auth()->user()->hasAnyRole(['admin','owner','super_admin']))
        {!! $sidebarItem(route('hr.index'), 'HR Management', '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>', 'hr.index') !!}
        @endif
        {!! $sidebarDivider !!}
        @endif

        @else
        {{-- ═══ SUPER ADMIN MENU ═══ --}}

        {!! $sidebarSection('Main') !!}

        {!! $sidebarItem(
            route('dashboard'),
            'Dashboard',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
            'dashboard'
        ) !!}

        {!! $sidebarDivider !!}
        {!! $sidebarSection('Organizations') !!}

        {!! $sidebarItem(
            route('superadmin.organizations'),
            'All Organizations',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
            'superadmin.organizations*'
        ) !!}

        {!! $sidebarItem(
            route('superadmin.users'),
            'All Users',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
            'superadmin.users*'
        ) !!}

        {!! $sidebarItem(
            route('superadmin.designations'),
            'Designations',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
            'superadmin.designations*'
        ) !!}

        {!! $sidebarDivider !!}
        {!! $sidebarSection('Platform') !!}

        {!! $sidebarItem(
            route('agent.health'),
            'Platform Health',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            'agent.*'
        ) !!}

        @if(Route::has('billing.index'))
        {!! $sidebarItem(
            route('billing.index'),
            'Billing Overview',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
            'billing*'
        ) !!}
        @endif

        {!! $sidebarDivider !!}
        {!! $sidebarSection('System') !!}

        {!! $sidebarItem(
            route('settings.index'),
            'Settings',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'settings*'
        ) !!}

        {!! $sidebarItem(
            route('notifications.index'),
            'Notifications',
            '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
            'notifications*'
        ) !!}

        {!! $sidebarDivider !!}

        @if(\Illuminate\Support\Facades\Route::has('pricing'))
        <div style="padding:8px 12px">
            <a href="{{ route('pricing') }}"
               style="font-size:12px;color:#6b7280;text-decoration:none;
                      display:block;padding:6px 8px;border-radius:6px;
                      background:#f9fafb;text-align:center">
                View Plans
            </a>
        </div>
        @endif

        @endif

    </div>
</div>

{{-- Mobile sidebar overlay --}}
<div id="fv-overlay" onclick="closeMobileSidebar()" class="mobile-overlay"></div>

<script>window.NAV_CSRF='{{ csrf_token() }}';</script>
<script src="{{ asset('js/navigation.js') }}" defer></script>
