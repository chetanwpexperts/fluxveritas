@extends('layouts.app')
@section('title', 'Fairness Engine')

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Fairness Report</h1>
        <p class="page-subtitle">AI-powered analysis of workload equity and team fairness</p>
    </div>
    <div class="page-header-right">
        @can('run_fairness_analysis')
        <form method="POST" action="{{ route('fairness.run') }}">
            @csrf
            <button type="submit" class="btn-primary">Run Analysis</button>
        </form>
        @endcan
        <a href="{{ route('dependency.index') }}" class="btn-secondary">&larr; Blockers</a>
    </div>
</div>

    @if(session('success'))
        <div class="fv-alert fv-alert-success mb-lg">{{ session('success') }}</div>
    @endif

    {{-- Layer 2 Context Warning --}}
    @php
        $onLeave  = count($context['users_on_leave'] ?? []);
        $resigned = count($context['users_resigned'] ?? []);
        $openBlockersCount = ($context['open_blockers'] ?? collect())->count();
        $waitingDepsCount  = ($context['waiting_dependencies'] ?? collect())->count();
        $hasContext = $onLeave || $resigned || $openBlockersCount || $waitingDepsCount;
    @endphp

    @if($hasContext)
    <div class="fairness-context-banner">
        <div class="fairness-context-title">Layer 2 Context Active</div>
        <p class="fairness-context-desc">
            The fairness engine is aware of the following org context when generating flags.
            Users in the list below are excluded from imbalance calculations.
        </p>
        <div class="fairness-context-items">
            @if($onLeave)
            <div class="fairness-context-item"><strong>{{ $onLeave }}</strong> user{{ $onLeave > 1 ? 's' : '' }} on leave</div>
            @endif
            @if($resigned)
            <div class="fairness-context-item"><strong>{{ $resigned }}</strong> resigned</div>
            @endif
            @if($openBlockersCount)
            <div class="fairness-context-item"><strong>{{ $openBlockersCount }}</strong> open blocker{{ $openBlockersCount > 1 ? 's' : '' }}</div>
            @endif
            @if($waitingDepsCount)
            <div class="fairness-context-item"><strong>{{ $waitingDepsCount }}</strong> waiting dependenc{{ $waitingDepsCount > 1 ? 'ies' : 'y' }}</div>
            @endif
        </div>
    </div>
    @endif

    {{-- Flags --}}
    @if($flags->isEmpty())
    <div class="fv-card fairness-empty-card">
        <div class="fairness-empty-icon">✓</div>
        <div class="fairness-empty-title">No fairness flags</div>
        <div class="fairness-empty-desc">Run an analysis to check for workload and equity issues.</div>
    </div>
    @else
    <div class="fairness-flag-list" id="fairness-list">
        @foreach($flags as $flag)
        @php
            $severityCard = match($flag->severity) {
                'high'   => 'fairness-flag-card-high',
                'medium' => 'fairness-flag-card-medium',
                default  => 'fairness-flag-card-low',
            };
            $severityBadge = match($flag->severity) {
                'high'   => 'fv-badge fv-badge-error',
                'medium' => 'fv-badge fv-badge-warning',
                default  => 'fv-badge fv-badge-gray',
            };
            $layerLabel = $flag->layer === 2 ? 'Layer 2 · Context-Aware' : 'Layer 1 · Statistical';
        @endphp
        <div class="fairness-flag-card {{ $severityCard }}">
            <div class="fairness-flag-inner">
                <div>
                    <div class="fairness-flag-badges">
                        <span class="{{ $severityBadge }}">{{ $flag->severity }}</span>
                        <span class="fairness-layer-badge">{{ $layerLabel }}</span>
                        <span class="fairness-flag-type">{{ str_replace('_', ' ', $flag->flag_type) }}</span>
                    </div>
                    <div class="fairness-flag-title">
                        {{ $flag->suggested_action ?? str_replace('_', ' ', ucfirst($flag->flag_type)) }}
                    </div>
                </div>
                <div class="fairness-flag-right">
                    <div class="text-right">
                        <div class="fairness-confidence">{{ round($flag->confidence_score * 100) }}%</div>
                        <div class="fairness-confidence-sub">confidence</div>
                    </div>
                    @if($flag->status !== 'dismissed')
                    @can('dismiss_flags')
                    <form method="POST" action="{{ route('fairness.dismiss', $flag->id) }}">
                        @csrf
                        <button type="submit" class="fairness-dismiss-btn" title="Dismiss">Dismiss</button>
                    </form>
                    @endcan
                    @else
                    <span class="fairness-dismissed-label">Dismissed</span>
                    @endif
                </div>
            </div>

            {{-- Layer 2 context detail --}}
            @if($flag->layer === 2 && $hasContext)
            <div class="fairness-layer2-context">
                <strong>Context applied:</strong>
                @if($onLeave) {{ $onLeave }} user(s) on leave excluded. @endif
                @if($resigned) {{ $resigned }} resigned user(s) excluded. @endif
                @if($openBlockersCount) {{ $openBlockersCount }} open blocker(s) considered. @endif
            </div>
            @endif

            {{-- Evidence --}}
            @if($flag->evidence)
            <details class="mt-sm fairness-evidence">
                <summary class="pointer text-muted-xs">View evidence</summary>
                <pre>{{ json_encode($flag->evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
            @endif

            <div class="fairness-flag-meta">
                Flagged {{ $flag->created_at->diffForHumans() }}
                @if($flag->flaggedUser) · {{ $flag->flaggedUser->name }} @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div id="fairness-pagination" class="pagination-wrapper"></div>
</div>

@push('scripts')
<script src="{{ asset('js/fairness-page.js') }}"></script>
@endpush
@endsection
