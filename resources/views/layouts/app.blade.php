<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'OutraqHQ')) — OutraqHQ</title>

        {{-- Critical: page loader — must render before any external CSS loads --}}
        <style>
            #page-loader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: #ffffff;
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: opacity 0.2s ease;
            }
            #page-loader.fade-out {
                opacity: 0;
                pointer-events: none;
            }
            .page-loader-inner {
                text-align: center;
            }
            .page-loader-spinner {
                width: 36px;
                height: 36px;
                border: 3px solid #e4e4e7;
                border-top-color: #18181b;
                border-radius: 50%;
                animation: loader-spin 0.6s linear infinite;
                margin: 0 auto;
            }
            @keyframes loader-spin {
                to { transform: rotate(360deg); }
            }
        </style>

        {{-- Fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preload"
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
            as="style"
            onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        </noscript>

        {{-- CSS — ordered: vite bundle → public CSS → stack --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @if(auth()->check() && auth()->user()->hasRole('super_admin'))
        <link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">
        @endif
        <link rel="stylesheet" href="{{ asset('css/navigation.css') }}">
        <link rel="stylesheet" href="{{ asset('css/components.css') }}">
        <link rel="stylesheet" href="{{ asset('css/pagination.css') }}">
        @stack('styles')
    </head>
    <body data-page="{{ request()->segment(1) ?: 'dashboard' }}" data-user-id="{{ auth()->id() }}" data-auth="{{ auth()->check() ? '1' : '0' }}" class="layout-body">

        {{-- Page loader: first child of body, hidden by page-loader.js after render --}}
        <div id="page-loader">
            <div class="page-loader-inner">
                <div class="page-loader-spinner"></div>
            </div>
        </div>

        {{-- Switched Org Banner --}}
        @if(session('switched_org'))
        <div class="banner-org-switch">
            <span>👁️ Viewing organization: <strong>{{ session('switched_org') }}</strong></span>
            <a href="{{ route('superadmin.switchBack') }}" class="banner-org-switch-btn">← Back to Platform</a>
        </div>
        @endif

        {{-- Impersonation Banner --}}
        @if(session('impersonating_from'))
        <div class="banner-impersonation">
            <span>⚠️ Impersonating <strong>{{ auth()->user()->name }}</strong></span>
            <a href="{{ route('superadmin.stop') }}" class="banner-impersonation-btn">Return to Super Admin</a>
        </div>
        @endif

        @include('layouts.navigation')

        {{-- Optional page-level header --}}
        @isset($header)
            <div class="slot-header">
                <div class="slot-header-inner">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <main id="fv-main" class="layout-main">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        {{-- Footer --}}
        <footer id="fv-footer" class="layout-footer">
            <div class="footer-inner">
                <div class="footer-brand">
                    <div class="footer-logo-mark">OQ</div>
                    <span class="footer-copyright">© 2026 OutraqHQ</span>
                </div>
                <div class="footer-user">
                    @auth
                    <span class="footer-user-name">
                        {{ auth()->user()->name }}
                        <span class="footer-role-badge">
                            {{ str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? auth()->user()->role ?? 'user') }}
                        </span>
                    </span>
                    @endauth
                    @if(\Illuminate\Support\Facades\Route::has('pricing'))
                    <a href="{{ route('pricing') }}" style="font-size:12px;color:#6b7280;text-decoration:none;margin-right:8px">View Plans</a>
                    @endif
                    <span class="footer-version">v1.0.0</span>
                </div>
            </div>
        </footer>

        {{-- ─── Flash Messages ────────────────────────────────────────────────── --}}
        @if(session('success'))
        <div data-flash-message class="flash-msg flash-msg-success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div data-flash-message class="flash-msg flash-msg-error">✗ {{ session('error') }}</div>
        @endif
        @if(session('warning'))
        <div data-flash-message class="flash-msg flash-msg-warning">⚠ {{ session('warning') }}</div>
        @endif
        @if(session('info'))
        <div data-flash-message class="flash-msg flash-msg-info">ℹ {{ session('info') }}</div>
        @endif

        {{-- Scripts — page-loader first, then shared libs, then page-specific --}}
        <script src="{{ asset('js/page-loader.js') }}"></script>
        <script src="{{ asset('js/pagination.js') }}"></script>
        @if(auth()->check() && auth()->user()->hasRole('super_admin'))
        <script src="{{ asset('js/superadmin.js') }}"></script>
        @endif
        @stack('scripts')

        @auth
        {{-- ─── Animated AI Agent ───────────────────────────────────────────────── --}}

        {{-- AGENT WRAPPER --}}
        <div id="fv-agent-wrap"
             data-user="{{ auth()->user()->name }}"
             data-role="{{ auth()->user()->getRoleNames()->first() ?? 'employee' }}">

            {{-- Speech Bubble --}}
            <div id="fv-agent-bubble" class="fv-agent-hidden" data-agent-action="toggle">
                {{-- visibility controlled by help-agent.js via fv-agent-hidden class --}}
                <span id="fv-bubble-text"></span>
                <button id="fv-bubble-close" data-agent-action="close-bubble" class="agent-bubble-close-btn">✕</button>
            </div>

            {{-- Agent Character --}}
            <div id="fv-agent-body" data-agent-action="toggle">
                <div id="outy-pulse-dot" class="outy-pulse-dot"></div>
                <svg class="agent-svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    {{-- FILTERS & GRADIENTS --}}
                    <defs>
                        <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                            <feMerge><feMergeNode in="coloredBlur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <filter id="strongGlow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                            <feMerge><feMergeNode in="coloredBlur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <radialGradient id="bodyGrad" cx="50%" cy="40%" r="60%">
                            <stop offset="0%" stop-color="#2d2d35"/>
                            <stop offset="100%" stop-color="#09090b"/>
                        </radialGradient>
                        <radialGradient id="headGrad" cx="50%" cy="35%" r="60%">
                            <stop offset="0%" stop-color="#1c1c24"/>
                            <stop offset="100%" stop-color="#09090b"/>
                        </radialGradient>
                        <radialGradient id="eyeGrad" cx="35%" cy="35%" r="65%">
                            <stop offset="0%" stop-color="#4ade80"/>
                            <stop offset="100%" stop-color="#10b981"/>
                        </radialGradient>
                        <radialGradient id="screenGrad" cx="50%" cy="40%" r="70%">
                            <stop offset="0%" stop-color="#0d1f1a"/>
                            <stop offset="100%" stop-color="#030d0a"/>
                        </radialGradient>
                        <linearGradient id="antennaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#10b981"/>
                            <stop offset="100%" stop-color="#059669"/>
                        </linearGradient>
                        <linearGradient id="armGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#27272a"/>
                            <stop offset="100%" stop-color="#18181b"/>
                        </linearGradient>
                    </defs>
                    {{-- PULSE RING --}}
                    <circle cx="40" cy="46" r="30" fill="none" stroke="#10b981" stroke-width="0.5" opacity="0.15" class="outy-pulse-ring"/>
                    {{-- ANTENNA --}}
                    <line x1="40" y1="10" x2="40" y2="4" stroke="url(#antennaGrad)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="40" cy="3" r="3" fill="#10b981" filter="url(#strongGlow)" class="agent-antenna-pulse"/>
                    <circle cx="40" cy="3" r="5" fill="none" stroke="#10b981" stroke-width="0.5" opacity="0.4" class="agent-antenna-pulse"/>
                    {{-- NECK --}}
                    <rect x="36" y="30" width="8" height="6" rx="2" fill="#1a1a22"/>
                    <line x1="36" y1="32" x2="44" y2="32" stroke="#10b981" stroke-width="0.5" opacity="0.4"/>
                    <line x1="36" y1="34" x2="44" y2="34" stroke="#10b981" stroke-width="0.5" opacity="0.4"/>
                    {{-- HEAD --}}
                    <rect x="14" y="8" width="52" height="26" rx="13" fill="url(#headGrad)"/>
                    <rect x="14" y="8" width="52" height="26" rx="13" fill="none" stroke="#10b981" stroke-width="0.8" opacity="0.3"/>
                    <rect x="16" y="9" width="20" height="4" rx="2" fill="white" opacity="0.04"/>
                    {{-- VISOR --}}
                    <rect x="18" y="12" width="44" height="18" rx="8" fill="url(#screenGrad)"/>
                    <rect x="18" y="12" width="44" height="18" rx="8" fill="none" stroke="#10b981" stroke-width="0.6" opacity="0.5"/>
                    <line x1="18" y1="17" x2="62" y2="17" stroke="#10b981" stroke-width="0.3" opacity="0.15"/>
                    <line x1="18" y1="22" x2="62" y2="22" stroke="#10b981" stroke-width="0.3" opacity="0.15"/>
                    <line x1="18" y1="27" x2="62" y2="27" stroke="#10b981" stroke-width="0.3" opacity="0.15"/>
                    {{-- LEFT EYE --}}
                    <circle cx="29" cy="21" r="6" fill="url(#eyeGrad)" filter="url(#glow)" class="agent-eye"/>
                    <circle cx="29" cy="21" r="3.5" fill="#065f46"/>
                    <circle cx="29" cy="21" r="2" fill="#10b981" filter="url(#strongGlow)"/>
                    <circle cx="27.5" cy="19.5" r="1" fill="white" opacity="0.9"/>
                    <circle cx="30" cy="22.5" r="0.5" fill="white" opacity="0.4"/>
                    {{-- RIGHT EYE --}}
                    <circle cx="51" cy="21" r="6" fill="url(#eyeGrad)" filter="url(#glow)" class="agent-eye"/>
                    <circle cx="51" cy="21" r="3.5" fill="#065f46"/>
                    <circle cx="51" cy="21" r="2" fill="#10b981" filter="url(#strongGlow)"/>
                    <circle cx="49.5" cy="19.5" r="1" fill="white" opacity="0.9"/>
                    <circle cx="52" cy="22.5" r="0.5" fill="white" opacity="0.4"/>
                    {{-- MOUTH --}}
                    <path id="agent-mouth" d="M32 27 Q40 32 48 27" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" fill="none" filter="url(#glow)"/>
                    {{-- BODY --}}
                    <rect x="18" y="36" width="44" height="30" rx="12" fill="url(#bodyGrad)"/>
                    <rect x="18" y="36" width="44" height="30" rx="12" fill="none" stroke="#10b981" stroke-width="0.8" opacity="0.25"/>
                    <rect x="20" y="37" width="16" height="3" rx="1.5" fill="white" opacity="0.04"/>
                    {{-- CHEST PANEL --}}
                    <rect x="26" y="42" width="28" height="16" rx="5" fill="#0a0a0f"/>
                    <rect x="26" y="42" width="28" height="16" rx="5" fill="none" stroke="#10b981" stroke-width="0.6" opacity="0.4"/>
                    <line x1="26" y1="50" x2="54" y2="50" stroke="#10b981" stroke-width="0.3" opacity="0.3"/>
                    {{-- CHEST DOTS --}}
                    <circle cx="33" cy="47" r="2.5" fill="#10b981" filter="url(#glow)" class="agent-dot-1"/>
                    <circle cx="40" cy="47" r="2.5" fill="#3b82f6" filter="url(#glow)" class="agent-dot-2"/>
                    <circle cx="47" cy="47" r="2.5" fill="#f59e0b" filter="url(#glow)" class="agent-dot-3"/>
                    <rect x="31" y="52" width="4" height="1.5" rx="0.75" fill="#10b981" opacity="0.4"/>
                    <rect x="38" y="52" width="4" height="1.5" rx="0.75" fill="#3b82f6" opacity="0.4"/>
                    <rect x="45" y="52" width="4" height="1.5" rx="0.75" fill="#f59e0b" opacity="0.4"/>
                    {{-- LEFT ARM --}}
                    <rect x="4" y="38" width="14" height="8" rx="4" fill="url(#armGrad)" class="agent-arm-wave"/>
                    <circle cx="18" cy="42" r="3" fill="#1a1a22" stroke="#10b981" stroke-width="0.5" opacity="0.6"/>
                    <line x1="7" y1="42" x2="13" y2="42" stroke="#10b981" stroke-width="0.5" opacity="0.3"/>
                    <circle cx="5" cy="42" r="3" fill="#1a1a22" stroke="#10b981" stroke-width="0.5" opacity="0.5"/>
                    {{-- RIGHT ARM --}}
                    <rect x="62" y="38" width="14" height="8" rx="4" fill="url(#armGrad)"/>
                    <circle cx="62" cy="42" r="3" fill="#1a1a22" stroke="#10b981" stroke-width="0.5" opacity="0.6"/>
                    <line x1="67" y1="42" x2="73" y2="42" stroke="#10b981" stroke-width="0.5" opacity="0.3"/>
                    <circle cx="75" cy="42" r="3" fill="#1a1a22" stroke="#10b981" stroke-width="0.5" opacity="0.5"/>
                    {{-- LEGS --}}
                    <rect x="24" y="65" width="12" height="10" rx="5" fill="#1a1a22"/>
                    <rect x="24" y="65" width="12" height="10" rx="5" fill="none" stroke="#10b981" stroke-width="0.5" opacity="0.3"/>
                    <rect x="28" y="73" width="4" height="2" rx="1" fill="#10b981" opacity="0.4"/>
                    <rect x="44" y="65" width="12" height="10" rx="5" fill="#1a1a22"/>
                    <rect x="44" y="65" width="12" height="10" rx="5" fill="none" stroke="#10b981" stroke-width="0.5" opacity="0.3"/>
                    <rect x="48" y="73" width="4" height="2" rx="1" fill="#10b981" opacity="0.4"/>
                    {{-- SIDE VENTS --}}
                    <line x1="19" y1="45" x2="19" y2="55" stroke="#10b981" stroke-width="0.5" opacity="0.2"/>
                    <line x1="61" y1="45" x2="61" y2="55" stroke="#10b981" stroke-width="0.5" opacity="0.2"/>
                </svg>
            </div>
        </div>

        {{-- CHAT WINDOW --}}
        <div id="fv-chat-window">
            {{-- Header --}}
            <div class="chat-header">
                <div class="chat-header-left">
                    <div class="chat-header-icon">🤖</div>
                    <div>
                        <div class="chat-header-title">OutraqHQ Agent</div>
                        <div class="chat-header-status">
                            <span class="chat-status-dot"></span>
                            Watching · Ready to help
                        </div>
                    </div>
                </div>
                <button data-agent-action="toggle" class="chat-close-btn">✕</button>
            </div>

            {{-- Messages --}}
            <div id="fv-chat-msgs" class="chat-msgs">
                <div class="chat-msg-row">
                    <div class="chat-msg-icon">🤖</div>
                    <div class="chat-msg-bubble">
                        <span id="fv-welcome-msg">Hi! I'm Outy 👋 I know everything about your work here. Ask me anything!</span>
                    </div>
                </div>
            </div>

            {{-- Quick questions — role-aware (HelpAgentService::quickActions) --}}
            <div class="chat-chips">
                @foreach(app(\App\Services\HelpAgentService::class)->quickActions(auth()->user()) as $action)
                    <button class="chat-chip-btn" data-agent-action="ask" data-question="{{ $action['question'] }}">{{ $action['label'] }}</button>
                @endforeach
            </div>

            {{-- Input --}}
            <div class="chat-input-row">
                <input id="fv-agent-input" type="text" placeholder="Ask me anything..."
                    class="chat-input">
                <button data-agent-action="send" id="fv-send-btn" class="chat-send-btn">Send</button>
            </div>
        </div>

        <script>window.FV_CSRF='{{ csrf_token() }}';window.FV_PAGE='{{ request()->segment(1) ?: "dashboard" }}';</script>
        <script src="{{ asset('js/help-agent.js') }}"></script>
        @endauth
        {{-- ─────────────────────────────────────────────────────────────────────── --}}
    </body>
</html>
