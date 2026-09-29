<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set your password — OutraqHQ</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
<div class="auth-split-container">
    <div class="auth-form-side">
        <h1 class="heading">Welcome, {{ $user->name }}</h1>
        <p class="sub">{{ $user->organization?->name }} added you to OutraqHQ. Choose a password to sign in with {{ $user->email }}.</p>

        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <div class="field">
                <label for="password">New password</label>
                <input type="password" id="password" name="password" required autofocus autocomplete="new-password">
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn">Set password and sign in</button>
        </form>
    </div>
</div>
</body>
</html>
