<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval — OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/pending.css') }}">
</head>
<body>
<div class="wrap">
    <div class="card">

        {{-- Logo --}}
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:36px;">
            <div style="width:36px; height:36px; background:#18181b; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.8rem; font-weight:800; color:white;">OQ</div>
            <span style="font-size:1rem; font-weight:800; color:#0f172a;">OutraqHQ</span>
        </div>

        {{-- Icon --}}
        <div style="width:72px; height:72px; background:#f4f4f5; border-radius:20px; display:flex; align-items:center; justify-content:center; margin-bottom:24px;">
            <svg style="width:36px; height:36px; color:#18181b;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:0.875rem; color:#166534;">
            <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:0.875rem; color:#dc2626;">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <h1 style="font-size:1.5rem; font-weight:800; color:#0f172a; letter-spacing:-0.02em; margin-bottom:6px; line-height:1.2;">
            Account Created Successfully!
        </h1>
        <p style="font-size:0.9rem; color:#64748b; margin-bottom:28px; line-height:1.6;">
            Your account is pending approval. An administrator needs to review and approve your account before you can access the platform.
            This usually takes less than 24 hours.
        </p>

        {{-- Info box --}}
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px 24px; margin-bottom:28px;">
            <div style="font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:14px;">Your Account Details</div>
            @foreach([
                ['Your Name', $user->name],
                ['Email', $user->email],
                ['Account Type', 'Team Member'],
            ] as [$label, $value])
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; {{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                <span style="font-size:0.8rem; color:#64748b;">{{ $label }}</span>
                <span style="font-size:0.8rem; font-weight:600; color:#0f172a;">{{ $value }}</span>
            </div>
            @endforeach
            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:8px;">
                <span style="font-size:0.8rem; color:#64748b;">Status</span>
                <span style="font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:20px; background:#fffbeb; color:#d97706; border:1px solid rgba(217,119,6,0.2);">
                    Pending Review
                </span>
            </div>
        </div>

        {{-- What happens next --}}
        <div style="margin-bottom:28px;">
            <div style="font-size:0.8rem; font-weight:700; color:#0f172a; margin-bottom:14px;">What happens next</div>
            <div class="step">
                <div class="step-num">1</div>
                <div style="font-size:0.8rem; color:#475569; padding-top:5px;">Admin receives notification of your registration.</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div style="font-size:0.8rem; color:#475569; padding-top:5px;">Admin reviews and approves your account, assigning you a role.</div>
            </div>
            <div class="step" style="margin-bottom:0;">
                <div class="step-num">3</div>
                <div style="font-size:0.8rem; color:#475569; padding-top:5px;">You gain access to the platform and can start collaborating.</div>
            </div>
        </div>

        {{-- Token activation --}}
        <div style="background:#f4f4f5; border:1px solid #e4e4e7; border-radius:12px; padding:20px 24px; margin-bottom:28px;">
            <div style="font-size:0.875rem; font-weight:700; color:#18181b; margin-bottom:6px;">Already have an invite link?</div>
            <div style="font-size:0.8rem; color:#52525b; margin-bottom:14px;">Enter your invite token to activate your account immediately.</div>
            <form method="POST" action="{{ route('onboarding.activate') }}" style="display:flex; gap:8px;">
                @csrf
                <input type="text" name="invite_token" placeholder="Paste your invite token…"
                       style="flex:1; padding:9px 12px; border:1px solid #e4e4e7; border-radius:8px;
                              font-size:0.8rem; background:white; color:#09090b; outline:none;">
                <button type="submit" class="fv-btn fv-btn-primary" style="font-size:0.8rem; white-space:nowrap;">
                    Activate
                </button>
            </form>
        </div>

        {{-- Log out --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    style="width:100%; padding:11px; background:white; color:#94a3b8; border:1px solid #e2e8f0;
                           border-radius:10px; font-size:0.875rem; font-weight:600; cursor:pointer;">
                Log Out
            </button>
        </form>

    </div>
</div>
</body>
</html>
