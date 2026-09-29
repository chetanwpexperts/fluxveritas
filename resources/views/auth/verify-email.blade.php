<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
<div class="card">
    <a href="/" class="logo">
        <div class="logo-mark">OQ</div>
        <span class="logo-name">OutraqHQ</span>
    </a>

    <div class="icon-wrap">📧</div>
    <h1 class="heading">Check your email</h1>
    <p class="sub">Thanks for signing up! Please verify your email address by clicking the link we just sent you.</p>

    @if(session('status') == 'verification-link-sent')
    <div class="status-box">A new verification link has been sent to your email address.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn">Resend Verification Email</button>
    </form>

    <div class="divider">
        <div class="divider-line"></div>
        <span class="divider-text">or</span>
        <div class="divider-line"></div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-ghost">Log out</button>
    </form>
</div>
</body>
</html>
