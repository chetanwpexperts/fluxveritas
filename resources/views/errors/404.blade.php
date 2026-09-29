<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}">
</head>
<body class="error-body">
    <div class="error-container">
        <a href="{{ url('/') }}" class="error-brand">
            <div class="error-logo">OQ</div>
            <span class="error-brand-name">OutraqHQ</span>
        </a>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">The page you're looking for doesn't exist or has been moved.</p>
        <div class="error-actions">
            <a href="{{ route('dashboard') }}" class="error-btn-primary">Go to Dashboard</a>
            <a href="{{ url('/') }}" class="error-btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>
