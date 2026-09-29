@extends('layouts.app')
@section('title', 'Check your import')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/import.css') }}">
@endpush

@php
    $stats  = $preview['stats'] ?? [];
    $new    = $preview['new'] ?? ['departments' => [], 'teams' => [], 'designations' => []];
    $issues = $preview['issues'] ?? [];
    $sample = $preview['sample'] ?? [];
    $toSave = ($stats['create'] ?? 0) + ($stats['update'] ?? 0);
@endphp

@section('content')
<div class="im-page">
    @include('import._steps', ['current' => 3])
    <h1 class="im-title">Check before importing</h1>
    <p class="im-sub">Nothing has been saved yet. Rows with errors are left out; fix them in your file and import it again later.</p>

    @if($errors->any())
        <div class="im-alert im-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    @if($seatError)
        <div class="im-alert im-alert-error" role="alert">{{ $seatError }} <a class="im-link" href="{{ route('billing.index') }}">See plans</a></div>
    @endif

    <section class="im-card">
        <div class="im-stats">
            <div class="im-stat"><b>{{ number_format($stats['total'] ?? 0) }}</b><span>Rows in file</span></div>
            <div class="im-stat im-stat-create"><b>{{ number_format($stats['create'] ?? 0) }}</b><span>New people</span></div>
            <div class="im-stat"><b>{{ number_format($stats['update'] ?? 0) }}</b><span>Updates</span></div>
            <div class="im-stat"><b>{{ number_format($stats['skip'] ?? 0) }}</b><span>Skipped</span></div>
            <div class="im-stat im-stat-error"><b>{{ number_format($stats['error'] ?? 0) }}</b><span>Errors</span></div>
        </div>

        @if(array_filter($new))
            <p class="im-muted">Will also create:</p>
            <ul class="im-list">
                @foreach(['departments' => 'Departments', 'teams' => 'Teams', 'designations' => 'Designations'] as $key => $label)
                    @if(!empty($new[$key]))
                        <li><strong>{{ $label }} ({{ count($new[$key]) }}):</strong> {{ collect($new[$key])->take(12)->implode(', ') }}{{ count($new[$key]) > 12 ? ' and ' . (count($new[$key]) - 12) . ' more' : '' }}</li>
                    @endif
                @endforeach
            </ul>
        @endif
    </section>

    @if($issues)
    <section class="im-card" aria-labelledby="issues-title">
        <h2 class="im-card-title" id="issues-title">Rows that need attention</h2>
        <p class="im-muted">
            Showing {{ count($issues) }}{{ ($stats['error'] + $stats['skip'] + $stats['warning']) > count($issues) ? ' of ' . number_format($stats['error'] + $stats['skip'] + $stats['warning']) : '' }}.
            <a class="im-link" href="{{ route('import.employees.issues', $import) }}">Download the full list (CSV)</a>
        </p>
        <div class="im-table-wrap">
            <table class="im-table">
                <thead><tr><th>Line</th><th>Email</th><th>Result</th><th>Details</th></tr></thead>
                <tbody>
                @foreach($issues as $issue)
                    <tr>
                        <td>{{ $issue['line'] }}</td>
                        <td>{{ $issue['email'] ?: '—' }}</td>
                        <td><span class="im-pill im-pill-{{ $issue['level'] }}">{{ ucfirst($issue['level']) }}</span></td>
                        <td>{{ implode('; ', $issue['messages']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    @if($sample)
    <section class="im-card" aria-labelledby="sample-title">
        <h2 class="im-card-title" id="sample-title">First rows as they will be imported</h2>
        <div class="im-table-wrap">
            <table class="im-table">
                <thead><tr><th>Line</th><th></th><th>Name</th><th>Email</th><th>Department / team</th><th>Designation</th><th>Manager</th><th>Joined</th></tr></thead>
                <tbody>
                @foreach($sample as $row)
                    <tr>
                        <td>{{ $row['line'] }}</td>
                        <td><span class="im-pill im-pill-{{ $row['action'] }}">{{ $row['action'] === 'create' ? 'New' : 'Update' }}</span></td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['email'] }}</td>
                        <td>{{ collect([$row['department'], $row['team']])->filter()->implode(' / ') ?: '—' }}</td>
                        <td>{{ $row['designation'] ?: '—' }}</td>
                        <td>{{ $row['manager_email'] ?: ($row['manager_name'] ?: '—') }}</td>
                        <td>{{ $row['join_date'] ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    <form method="POST" action="{{ route('import.employees.run', $import) }}" class="im-actions">
        @csrf
        @if($queued)
            <span class="im-muted">Large file — it will import in the background and you can leave this page.</span>
        @endif
        <a class="im-link" href="{{ route('import.employees.mapping', $import) }}">Change column matches</a>
        <button type="submit" class="im-btn im-btn-primary" @disabled($toSave === 0 || $seatError)>
            Import {{ number_format($toSave) }} {{ $toSave === 1 ? 'person' : 'people' }}
        </button>
    </form>
</div>
@endsection
