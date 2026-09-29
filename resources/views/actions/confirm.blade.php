@extends('actions._page')
@section('title', 'Approve increment')

@section('body')
    <h1 class="heading">Approve {{ $review->user->name }}'s increment?</h1>
    <p class="sub">This link was sent to {{ $user->name }}. Check the details, then confirm.</p>

    <dl class="action-facts">
        <dt>Employee</dt><dd>{{ $review->user->name }}</dd>
        <dt>Review year</dt><dd>{{ $review->review_year }}</dd>
        <dt>Average score</dt><dd>{{ round((float) $review->avg_score, 1) }}</dd>
        <dt>Increment to approve</dt><dd>{{ $final }}%</dd>
    </dl>

    <form method="POST" action="{{ route('action.execute.confirm') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button type="submit" class="btn">Approve {{ $final }}%</button>
    </form>
    <p class="sub">To approve a different amount, use <a href="{{ route('increment.reviews') }}">Increment Reviews</a> in the app. This link works once.</p>
@endsection
