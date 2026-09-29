@extends('layouts.app')
@section('title', 'Import progress')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/import.css') }}">
@endpush

@php
    $summary = $import->summary ?? [];
    $new     = $summary['new'] ?? [];
    $done    = $import->status === 'completed';
@endphp

@section('content')
<div class="im-page">
    @include('import._steps', ['current' => 4])
    <h1 class="im-title">{{ $done ? 'Import finished' : ($import->status === 'failed' ? 'Import stopped' : 'Importing…') }}</h1>
    <p class="im-sub">{{ $import->original_name }} · started by {{ $import->importer?->name ?? 'someone' }} · {{ $import->created_at->format('j M Y, g:i A') }}</p>

    @if(session('success'))
        <div class="im-alert im-alert-success">{{ session('success') }}</div>
    @endif

    <section class="im-card" @unless($import->isFinished()) data-progress-url="{{ route('import.employees.progress', $import) }}" @endunless>
        <progress class="im-progress {{ $done ? 'is-done' : ($import->status === 'failed' ? 'is-failed' : '') }}" max="100"
                  value="{{ $done ? 100 : $import->progressPercent() }}" data-progress-bar aria-label="Import progress"></progress>
        <p class="im-muted" data-progress-label>
            {{ number_format($import->processed_rows) }} of {{ number_format($import->total_rows) }} rows ({{ $done ? 100 : $import->progressPercent() }}%)
        </p>
        @if($import->status === 'queued')
            <p class="im-muted">Waiting for the background worker to pick this up. You can leave this page — the import keeps going.</p>
        @endif
        @if($import->status === 'failed')
            <div class="im-alert im-alert-error">{{ $import->failure_message }}</div>
        @endif
    </section>

    @if($done)
    <section class="im-card">
        <div class="im-stats">
            <div class="im-stat im-stat-create"><b>{{ number_format($import->created_count) }}</b><span>Added</span></div>
            <div class="im-stat"><b>{{ number_format($import->updated_count) }}</b><span>Updated</span></div>
            <div class="im-stat"><b>{{ number_format($import->skipped_count) }}</b><span>Skipped</span></div>
            <div class="im-stat im-stat-error"><b>{{ number_format($import->error_count) }}</b><span>Errors</span></div>
            <div class="im-stat"><b>{{ number_format($summary['managers_linked'] ?? 0) }}</b><span>Managers linked</span></div>
        </div>
        @if(array_filter($new))
            <ul class="im-list">
                @foreach(['departments' => 'departments', 'teams' => 'teams', 'designations' => 'designations'] as $key => $label)
                    @if(!empty($new[$key]))<li>Created {{ count($new[$key]) }} {{ $label }}</li>@endif
                @endforeach
            </ul>
        @endif

        <div class="im-actions">
            @if($import->errors_path)
                <a class="im-btn im-btn-secondary" href="{{ route('import.employees.issues', $import) }}">Download error report</a>
            @endif
            @if(!$import->invites_sent_at && count($summary['created_user_ids'] ?? []) > 0)
                <form method="POST" action="{{ route('import.employees.invites', $import) }}">
                    @csrf
                    <button type="submit" class="im-btn im-btn-primary">Send invitations to {{ count($summary['created_user_ids']) }} people</button>
                </form>
            @elseif($import->invites_sent_at)
                <span class="im-muted">Invitations sent {{ $import->invites_sent_at->diffForHumans() }}.</span>
            @endif
            <a class="im-btn im-btn-secondary" href="{{ route('directory.index') }}">Open the directory</a>
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/import.js') }}"></script>
@endpush
