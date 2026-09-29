<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — OutraqHQ</title>
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

    <h1 class="heading">Set new password</h1>
    <p class="sub">Choose a strong password for your account.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" placeholder="you@company.com" required autocomplete="username" readonly>
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" placeholder="Min. 8 characters" required autocomplete="new-password" autofocus>
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat password" required autocomplete="new-password">
            @error('password_confirmation')<p class="error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn">Reset Password</button>
    </form>
</div>
</body>
</html>
