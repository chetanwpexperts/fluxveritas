<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OutraqHQ') — OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
        <a href="/#features" class="lnav-link">Features</a>
        <a href="/tour"      class="lnav-link">Tour</a>
        <a href="/pricing"   class="lnav-link">Pricing</a>
        <a href="/docs"      class="lnav-link">Docs</a>
    </div>
    <div class="lnav-actions">
        @auth
            <a href="{{ route('dashboard') }}" class="lbtn-em">Dashboard</a>
        @else
            <a href="/login"    class="lbtn-ghost">Log in</a>
            <a href="/register" class="lbtn-em">Get Started Free</a>
        @endauth
    </div>
    <button class="lnav-ham" onclick="toggleMobileMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</nav>
<div class="lnav-mobile" id="lnav-mobile">
    <a href="/#features">Features</a>
    <a href="/tour">Tour</a>
    <a href="/pricing">Pricing</a>
    <a href="/docs">Docs</a>
    <div class="lnav-mobile-btns">
        @auth
            <a href="{{ route('dashboard') }}" class="lbtn-em">Dashboard</a>
        @else
            <a href="/login"    class="lbtn-ghost">Log in</a>
            <a href="/register" class="lbtn-em">Get Started Free</a>
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
            <p class="lfooter-brand-tag">Where hard work is always seen, always protected, always rewarded. AI-powered team intelligence for the modern workplace.</p>
            <p class="lfooter-copy">© 2025 OutraqHQ by OutraqHQ.<br>All rights reserved.</p>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Product</div>
            <a href="/#features">Features</a>
            <a href="/tour">Tour</a>
            <a href="/pricing">Pricing</a>
            <a href="/docs">Docs</a>
            <a href="#">Changelog</a>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Company</div>
            <a href="#">About</a>
            <a href="#">Blog</a>
            <a href="#">Careers</a>
            <a href="{{ route('contact') }}">Contact</a>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Legal</div>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Security</a>
        </div>
    </div>
    <div class="lfooter-bottom">
        <p style="font-size:0.75rem;color:#52525b;">Built with ❤️ for teams that deserve better.</p>
        <div class="lfooter-socials">
            <a href="#" class="lfooter-social" title="Twitter/X">𝕏</a>
            <a href="#" class="lfooter-social" title="LinkedIn">in</a>
            <a href="#" class="lfooter-social" title="GitHub">⌥</a>
        </div>
    </div>
</footer>

<script src="{{ asset('js/landing.js') }}"></script>
@stack('scripts')
</body>
</html>
