@extends('layouts.app')
@section('title', 'Import employees')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/import.css') }}">
@endpush

@section('content')
<div class="im-page">
    @include('import._steps', ['current' => 1])
    <h1 class="im-title">Import employees</h1>
    <p class="im-sub">Bring your people in from a spreadsheet or another HR system. You'll match the columns and check everything before anything is saved.</p>

    @if($errors->any())
        <div class="im-alert im-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form class="im-card" method="POST" action="{{ route('import.employees.upload') }}" enctype="multipart/form-data">
        @csrf
        <div class="im-grid">
            <div class="im-field">
                <label for="preset">Where is this file from?</label>
                <select id="preset" name="preset" class="im-select">
                    @foreach($presets as $key => $label)
                        <option value="{{ $key }}" @selected(old('preset', 'custom') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="im-muted">Helps match column names like "Date of Joining" or "Work Email" automatically.</span>
            </div>
            <div class="im-field">
                <label for="file">File (.csv or .xlsx)</label>
                <input id="file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="im-input" required>
                <span class="im-muted">Up to {{ number_format($maxRows) }} rows. The first row must be the column names.</span>
            </div>
        </div>
        <div class="im-actions">
            <a class="im-link" href="{{ route('import.employees.template') }}">Download a blank template</a>
            <button type="submit" class="im-btn im-btn-primary">Upload and match columns</button>
        </div>
    </form>

    @if($recent->isNotEmpty())
    <section class="im-card" aria-labelledby="recent-title">
        <h2 class="im-card-title" id="recent-title">Recent imports</h2>
        <div class="im-table-wrap">
            <table class="im-table">
                <thead><tr><th>File</th><th>Rows</th><th>Created</th><th>Updated</th><th>Status</th><th>When</th></tr></thead>
                <tbody>
                @foreach($recent as $item)
                    <tr>
                        <td><a class="im-link" href="{{ in_array($item->status, ['uploaded']) ? route('import.employees.mapping', $item) : ($item->status === 'validated' ? route('import.employees.preview', $item) : route('import.employees.show', $item)) }}">{{ $item->original_name }}</a></td>
                        <td>{{ number_format($item->total_rows) }}</td>
                        <td>{{ number_format($item->created_count) }}</td>
                        <td>{{ number_format($item->updated_count) }}</td>
                        <td><span class="im-pill im-pill-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                        <td>{{ $item->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection
