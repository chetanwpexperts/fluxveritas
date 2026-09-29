@extends('actions._page')
@section('title', $title)

@section('body')
    <h1 class="heading">{{ $title }}</h1>
    <p class="sub">{{ $message }}</p>
    <a class="btn" href="{{ route('dashboard') }}">Open OutraqHQ</a>
@endsection
