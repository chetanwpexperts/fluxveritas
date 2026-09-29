{{-- Standalone layout for email action links — works without being signed in --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — OutraqHQ</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
<div class="auth-split-container">
    <div class="auth-form-side">
        @yield('body')
    </div>
</div>
</body>
</html>
