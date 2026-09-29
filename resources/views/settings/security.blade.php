@extends('layouts.app')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Security Overview --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Security Overview</h2>
                </div>
                <div class="p-lg">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        @foreach([
                            ['CSRF Protection',          true,                               'Active on all forms'],
                            ['SQL Injection Prevention', true,                               'Laravel query builder'],
                            ['XSS Prevention',           true,                               'Blade auto-escaping'],
                            ['Rate Limiting',            true,                               'Active on all routes'],
                            ['Session Encryption',       true,                               'AES-256 encryption'],
                            ['Password Hashing',         true,                               'Argon2id algorithm'],
                            ['HTTPS Only',               config('app.env') === 'production', 'Enforced in production'],
                            ['Debug Mode Off',           !config('app.debug'),               config('app.debug') ? 'WARNING: Debug is ON' : 'Debug is OFF'],
                        ] as [$check, $status, $note])
                        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#fafafa;border-radius:8px;border:1px solid #e4e4e7;">
                            <span style="color:{{ $status ? '#16a34a' : '#dc2626' }};font-size:1rem;flex-shrink:0;margin-top:1px;">{{ $status ? '✓' : '✗' }}</span>
                            <div>
                                <div style="font-size:0.8rem;font-weight:600;color:#09090b;">{{ $check }}</div>
                                <div style="font-size:0.72rem;color:#71717a;margin-top:2px;">{{ $note }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Current Session --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Current Session</h2>
                </div>
                <div class="p-lg">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">IP Address</div>
                            <div style="font-size:0.875rem;color:#09090b;">{{ request()->ip() }}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Browser</div>
                            <div style="font-size:0.875rem;color:#09090b;word-break:break-all;">{{ substr(request()->userAgent(), 0, 60) }}...</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Session Driver</div>
                            <div style="font-size:0.875rem;color:#09090b;">{{ config('session.driver') }}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Session Lifetime</div>
                            <div style="font-size:0.875rem;color:#09090b;">{{ config('session.lifetime') }} minutes</div>
                        </div>
                    </div>
                </div>
            </div>

    </div>

</div>
@endsection
