@extends('actions._page')
@section('title', 'Link not valid')

@section('body')
    <h1 class="heading">This link can't be used</h1>
    <div class="auth-alert auth-alert-error" role="alert">{{ $reason ?? 'This link has expired or has already been used.' }}</div>
    <p class="sub">Nothing was changed. Sign in to OutraqHQ to review and approve increments.</p>
    <a class="btn" href="{{ route('login') }}">Sign in</a>
@endsection
