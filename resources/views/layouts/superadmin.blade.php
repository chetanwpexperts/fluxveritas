<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.github.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;">
    <title>@yield('title', 'Super Admin') — OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pagination.css') }}">
    @stack('styles')
</head>
<body class="sa-layout-body">

    {{-- TOP BAR --}}
    <div class="sa-topbar">
        ⚡ SUPER ADMIN MODE — Platform Control Center
    </div>

    {{-- NAVBAR --}}
    <nav class="sa-navbar">
        <div class="sa-navbar-left">
            <a href="{{ route('superadmin.index') }}" class="sa-brand">
                <div class="sa-brand-logo">OQ</div>
                <span class="sa-brand-name">OutraqHQ</span>
                <span class="sa-brand-badge">SUPER ADMIN</span>
            </a>
        </div>

        <div class="sa-navbar-center">
            <a href="{{ route('superadmin.index') }}"
                class="sa-nav-link {{ request()->routeIs('superadmin.index') ? 'active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('superadmin.organizations') }}"
                class="sa-nav-link {{ request()->routeIs('superadmin.organizations') || request()->routeIs('superadmin.organizations.*') ? 'active' : '' }}">
                Organizations
            </a>
            <a href="{{ route('superadmin.users') }}"
                class="sa-nav-link {{ request()->routeIs('superadmin.users') ? 'active' : '' }}">
                Users
            </a>
            <a href="{{ route('superadmin.designations') }}"
                class="sa-nav-link {{ request()->routeIs('superadmin.designations') ? 'active' : '' }}">
                Designations
            </a>
            <a href="{{ route('agent.health') }}"
                class="sa-nav-link {{ request()->routeIs('agent.health') ? 'active' : '' }}">
                🏥 Health
            </a>
        </div>

        <div class="sa-navbar-right">
            <a href="{{ route('dashboard') }}" class="sa-back-btn">
                ← Back to App
            </a>
            <span class="sa-user-name">{{ auth()->user()->name }}</span>
        </div>
    </nav>

    {{-- MAIN CONTENT --}}
    <main class="sa-main">
        @if(session('success'))
            <div class="sa-alert sa-alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="sa-alert sa-alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/superadmin.js') }}"></script>
    <script src="{{ asset('js/pagination.js') }}"></script>
    @stack('scripts')

</body>
</html>
