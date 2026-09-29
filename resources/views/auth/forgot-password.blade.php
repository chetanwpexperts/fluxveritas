<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

<div class="auth-split-container" style="max-width:850px;">

    <!-- Left Rich Graphic Side -->
    <div class="auth-graphic-side">
        <div>
            <a href="/" class="graphic-logo">
                <div class="graphic-logo-mark">OQ</div>
                <span class="graphic-logo-text">OutraqHQ</span>
            </a>

            <div class="graphic-telemetry-box">
                <div class="telemetry-box-head">
                    <span class="telemetry-box-title">🔒 Password Recovery</span>
                    <span class="telemetry-box-badge">SECURE</span>
                </div>
                <div class="telemetry-item">
                    • Single-use encrypted reset token link will be sent to your corporate email.
                </div>
                <div class="telemetry-score-card">
                    <div class="telemetry-score-num">256-Bit</div>
                    <div class="telemetry-score-lbl">HMAC TOKEN ENCRYPTION</div>
                </div>
            </div>
        </div>

        <div class="graphic-footer">
            🔒 High Security Workspace Protocol
        </div>
    </div>

    <!-- Right Sleek Form Side -->
    <div class="auth-form-side">

        <h1 class="heading">Reset Password</h1>
        <p class="sub">Enter your email and we'll send you an encrypted recovery link.</p>

        @if (session('status'))
        <div style="background:#d1fae5; color:#065f46; padding:10px; border-radius:8px; font-size:13px; margin-bottom:16px; font-weight:600;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="field">
                <label for="email">Work Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn">Send Password Recovery Link →</button>
        </form>

        <div style="text-align:center; margin-top:20px;">
            <a href="{{ route('login') }}" style="color:#64748b; font-size:13px; text-decoration:none; font-weight:600;">← Back to Sign In</a>
        </div>
    </div>

</div>

</body>
</html>
