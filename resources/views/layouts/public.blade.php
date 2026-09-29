<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Section values are already escaped by @section('x', 'value') --}}
    <title>{!! $__env->hasSection('full_title') ? trim($__env->yieldContent('full_title')) : trim($__env->yieldContent('title', 'OutraqHQ')) . ' — OutraqHQ' !!}</title>
    @yield('meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @stack('styles')
</head>
<body>

{{-- HEADER --}}
<nav class="lnav" id="lnav">
    <a href="/" class="lnav-logo">
        <div class="lnav-mark">OQ</div>
        <span class="lnav-name">OutraqHQ</span>
    </a>
    <div class="lnav-links">
        <a href="/#product" class="lnav-link">Product</a>
        <a href="{{ route('pricing') }}" class="lnav-link">Pricing</a>
        <a href="{{ route('docs') }}" class="lnav-link">Docs</a>
        <a href="{{ route('contact') }}" class="lnav-link">Contact</a>
    </div>
    <div class="lnav-actions">
        @auth
            <a href="{{ route('dashboard') }}" class="lbtn-em">Go to dashboard</a>
        @else
            <a href="{{ route('login') }}" class="lbtn-ghost">Log in</a>
            <a href="{{ route('register') }}" class="lbtn-em">Start free</a>
        @endauth
    </div>
    <button class="lnav-ham" onclick="toggleMobileMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</nav>
<div class="lnav-mobile" id="lnav-mobile">
    <a href="/#product">Product</a>
    <a href="{{ route('pricing') }}">Pricing</a>
    <a href="{{ route('docs') }}">Docs</a>
    <a href="{{ route('contact') }}">Contact</a>
    <div class="lnav-mobile-btns">
        @auth
            <a href="{{ route('dashboard') }}" class="lbtn-em">Go to dashboard</a>
        @else
            <a href="{{ route('login') }}" class="lbtn-ghost">Log in</a>
            <a href="{{ route('register') }}" class="lbtn-em">Start free</a>
        @endauth
    </div>
</div>

{{-- PAGE CONTENT --}}
@yield('content')

{{-- FOOTER --}}
<footer class="lfooter">
    <div class="lfooter-grid">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div class="lnav-mark">OQ</div>
                <span style="font-size:0.95rem;font-weight:800;color:var(--text);letter-spacing:-0.02em;">OutraqHQ</span>
            </div>
            <p class="lfooter-brand-tag">HR and performance software for growing companies — people operations, work tracking and fair, data-backed reviews in one place.</p>
            <p class="lfooter-copy">© {{ date('Y') }} OutraqHQ. All rights reserved.</p>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Product</div>
            <a href="/#product">Features</a>
            <a href="{{ route('pricing') }}">Pricing</a>
            <a href="{{ route('tour') }}">Product tour</a>
            <a href="{{ route('docs') }}">Docs</a>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Company</div>
            <a href="{{ route('contact') }}">Contact</a>
            <a href="{{ route('contact', ['plan' => 'enterprise']) }}">Talk to sales</a>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Legal</div>
            <a href="{{ route('refund-policy') }}">Refund &amp; cancellation</a>
        </div>
    </div>
</footer>

<script src="{{ asset('js/landing.js') }}"></script>
@stack('scripts')
</body>
</html>
