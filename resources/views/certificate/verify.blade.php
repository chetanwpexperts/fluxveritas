@extends('layouts.app')

@section('content')
<div class="page-container flex-center min-h-60">
    <div class="fv-card p-xl max-w-xl text-center shadow-2xl rounded-2xl border border-emerald-500/30">
        <div class="dash-live-pill-green inline-block mb-md bg-emerald-500/20 text-emerald-300">
            <span class="live-dot bg-emerald-400"></span>
            <span class="dash-live-text-green text-emerald-300 font-bold">PUBLIC CRYPTOGRAPHIC FAIRNESS AUDIT</span>
        </div>

        <h1 class="heading-xl mb-xs">{{ $verification['org_name'] }}</h1>
        <p class="text-emerald-400 font-bold text-lg mb-lg">{{ $verification['status_label'] }}</p>

        <div class="fv-card p-md bg-slate-900/50 mb-lg text-left grid grid-cols-2 gap-md">
            <div>
                <div class="text-xs text-muted-xs">FAIRNESS INDEX</div>
                <div class="text-2xl font-bold text-white">{{ $verification['fairness_index'] }}%</div>
            </div>
            <div>
                <div class="text-xs text-muted-xs">ACTIVE EMPLOYEES</div>
                <div class="text-2xl font-bold text-white">{{ $verification['total_employees'] }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-xs">AUDITED AT</div>
                <div class="text-sm font-semibold text-white">{{ $verification['audited_at'] }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-xs">BIAS EVALUATION</div>
                <div class="text-sm font-semibold text-emerald-400">100% Mathematical Z-Score</div>
            </div>
        </div>

        <p class="text-muted-xs text-left mb-lg">
            This organization uses OutraqHQ's automated fairness engine. Performance reviews and salary increments are computed objectively by statistical algorithms, free from personal, racial, or gender bias.
        </p>

        <div class="text-2xs text-muted-xs font-mono break-all bg-slate-950 p-xs rounded">
            SHA256: {{ $verification['signature_hash'] }}
        </div>
    </div>
</div>
@endsection
