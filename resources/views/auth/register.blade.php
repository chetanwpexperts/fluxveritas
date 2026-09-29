<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workspace — OutraqHQ</title>
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
                    <span class="telemetry-box-title">🚀 Free Beta Access</span>
                    <span class="telemetry-box-badge">UNLIMITED</span>
                </div>
                <div class="telemetry-item">
                    ✓ Zero manual work telemetry tracking
                </div>
                <div class="telemetry-item">
                    ✓ Z-Score mathematical fairness calculator
                </div>
                <div class="telemetry-item">
                    ✓ Built-in Mini ERP (Payroll, Expenses, Assets)
                </div>
                <div class="telemetry-score-card">
                    <div class="telemetry-score-num">100%</div>
                    <div class="telemetry-score-lbl">UNBIASED WORKPLACE GUARANTEE</div>
                </div>
            </div>
        </div>

        <div class="graphic-footer">
            🔒 100% Multi-Tenant Data Isolation • Certified Workplace SaaS
        </div>
    </div>

    <!-- Right Sleek Form Side -->
    <div class="auth-form-side">

        <h1 class="heading">Launch Your Workspace</h1>
        <p class="sub">Already registered? <a href="{{ route('login') }}">Sign in here →</a></p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Alex Kumar" required autofocus>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="email">Work Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="alex@company.com" required>
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="password">Password (Min 8 Characters)</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
            </div>

            <div class="field">
                <label>Registration Type</label>
                <label class="type-card">
                    <div class="type-card-inner">
                        <input type="radio" name="account_type" value="create_org" checked style="margin-top:3px;">
                        <div>
                            <div class="type-title">Create New Organization</div>
                            <div class="type-desc">You will be the Owner & CEO with full command access.</div>
                        </div>
                    </div>
                </label>
                <label class="type-card">
                    <div class="type-card-inner">
                        <input type="radio" name="account_type" value="join_org" style="margin-top:3px;">
                        <div>
                            <div class="type-title">Join Existing Team</div>
                            <div class="type-desc">Join your company workspace via invite link.</div>
                        </div>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn">Create Autonomous Workspace →</button>
        </form>

        <div class="security-badge">
            <span>🛡️ Zero Credit Card Required for Beta</span>
        </div>
    </div>

</div>

</body>
</html>
