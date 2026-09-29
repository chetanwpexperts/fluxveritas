@extends('layouts.app')

@section('content')
<div class="page-container flex-center min-h-60">
    <div class="fv-card p-xl max-w-md text-center">
        <h1 class="heading-xl mb-sm text-red-500">Invalid Signature ⚠️</h1>
        <p class="text-muted-sm mb-lg">This cryptographic certificate signature is invalid or could not be verified.</p>
        <a href="{{ route('home') }}" class="fv-btn fv-btn-primary">Return Home</a>
    </div>
</div>
@endsection
