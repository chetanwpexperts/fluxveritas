<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error | OutraqHQ</title>
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
        <div class="error-code error-code-red">500</div>
        <h1 class="error-title">Server Error</h1>
        <p class="error-message">Something went wrong on our end. Our team has been notified. Please try again in a moment.</p>
        <div class="error-actions">
            <a href="{{ route('dashboard') }}" class="error-btn-primary">Go to Dashboard</a>
            <a href="javascript:history.back()" class="error-btn-secondary">Go Back</a>
        </div>
    </div>
</body>
</html>
