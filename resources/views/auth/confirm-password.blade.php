<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Password — OutraqHQ</title>
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

    <div class="icon-wrap">🔒</div>
    <h1 class="heading">Confirm your password</h1>
    <p class="sub">This is a secure area. Please re-enter your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password" autofocus>
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn">Confirm Password</button>
    </form>
</div>
</body>
</html>
