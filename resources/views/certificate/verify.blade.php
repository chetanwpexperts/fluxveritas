@extends('actions._page')
@section('title', 'Fairness certificate')

@section('body')
    <h1 class="heading">{{ $verification['org_name'] }}</h1>
    <p class="sub">{{ $verification['status_label'] }}</p>

    <dl class="action-facts">
        <dt>Fairness index</dt><dd>{{ $verification['fairness_index'] }}%</dd>
        <dt>Checked on</dt><dd>{{ $verification['audited_at'] }}</dd>
    </dl>

    <p class="sub">
        This organization uses OutraqHQ's fairness checks, which look for patterns such as uneven workload and long-open blockers
        and raise them for managers to review. The index reflects how many of those flags are currently open.
    </p>
@endsection
