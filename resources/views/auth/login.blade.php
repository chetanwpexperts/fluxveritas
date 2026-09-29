<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — OutraqHQ Command Workspace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

<div class="auth-split-container">

    <!-- Left Rich Graphic Side -->
    <div class="auth-graphic-side">
        <div>
            <a href="/" class="graphic-logo">
                <div class="graphic-logo-mark">OQ</div>
                <span class="graphic-logo-text">OutraqHQ</span>
            </a>

            <div class="graphic-telemetry-box">
                <div class="telemetry-box-head">
                    <span class="telemetry-box-title">⚡ Live Workspace Telemetry</span>
                    <span class="telemetry-box-badge">ACTIVE</span>
                </div>
                <div class="telemetry-item">
                    • <strong>Auto-Drafted Logs:</strong> 5:00 PM IST daily telemetry engine ready.
                </div>
                <div class="telemetry-item">
                    • <strong>CEO Co-Pilot:</strong> 30-sec morning AI executive digest active.
                </div>
                <div class="telemetry-score-card">
                    <div class="telemetry-score-num">96.8</div>
                    <div class="telemetry-score-lbl">ORGANIZATION FAIRNESS SCORE</div>
                </div>
            </div>
        </div>

        <div class="graphic-footer">
            🔒 Cryptographic SHA-256 Session Protection • Zero Manual Friction Workplace Intelligence
        </div>
    </div>

    <!-- Right Sleek Form Side -->
    <div class="auth-form-side">

        <!-- Role Selector Bar -->
        <div class="role-bar">
            <div class="role-tab active" onclick="setRolePrompt('ceo')">👔 CEO / Owner</div>
            <div class="role-tab" onclick="setRolePrompt('manager')">📈 Manager / HR</div>
            <div class="role-tab" onclick="setRolePrompt('employee')">💻 Employee</div>
        </div>

        <h1 class="heading" id="auth-heading">Sign In to Workspace</h1>
        <p class="sub" id="auth-sub">Don't have an account? <a href="{{ route('register') }}">Create Workspace →</a></p>

        @if(session('status'))
        <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
        @endif
        @foreach(['error', 'message'] as $flashKey)
            @if(session($flashKey))
            <div class="auth-alert auth-alert-error" role="alert">{{ session($flashKey) }}</div>
            @endif
        @endforeach

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="email">Corporate Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password">Security Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            @if(Route::has('password.request'))
            <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
                <a href="{{ route('password.request') }}" style="color:#64748b; font-size:12.5px; text-decoration:none; font-weight:600;">Forgot password?</a>
            </div>
            @endif
            <button type="submit" class="btn">Sign In to Command Center →</button>
        </form>

        <div class="security-badge">
            <span>🛡️ Certified Unbiased Workplace SaaS</span>
        </div>
    </div>

</div>

<script>
function setRolePrompt(role) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    const heading = document.getElementById('auth-heading');
    const sub = document.getElementById('auth-sub');

    if (role === 'ceo') {
        heading.textContent = 'Executive Portal Sign In';
        sub.innerHTML = 'Review morning digests & 1-click magic actions. <a href="{{ route("register") }}">Create Workspace →</a>';
    } else if (role === 'manager') {
        heading.textContent = 'Manager & HR Workspace';
        sub.innerHTML = 'Review team telemetry & expense claims. <a href="{{ route("register") }}">Create Workspace →</a>';
    } else {
        heading.textContent = 'Employee Telemetry Portal';
        sub.innerHTML = 'View your 5:00 PM auto-drafted work logs. <a href="{{ route("register") }}">Create Workspace →</a>';
    }
}
</script>

</body>
</html>
