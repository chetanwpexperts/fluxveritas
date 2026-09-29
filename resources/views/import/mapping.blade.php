@extends('layouts.app')
@section('title', 'Match columns')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/import.css') }}">
@endpush

@php
    $suggested = $import->summary['suggested'] ?? [];
    $samples   = $import->summary['samples'] ?? [];
    $mapping   = old('mapping', $import->mapping ?? []);
    $options   = $import->options ?? [];
@endphp

@section('content')
<div class="im-page">
    @include('import._steps', ['current' => 2])
    <h1 class="im-title">Match your columns</h1>
    <p class="im-sub">
        {{ $import->original_name }} · {{ number_format($import->total_rows) }} rows · {{ $presets[$import->preset] ?? 'Custom' }}.
        Green fields were matched automatically — check them, and choose "Don't import" for anything you don't need.
    </p>

    @if($errors->any())
        <div class="im-alert im-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('import.employees.mapping.save', $import) }}" data-import-mapping>
        @csrf
        <section class="im-card">
            <div class="im-alert im-alert-error im-hidden" data-duplicate-warning>The same field is chosen for more than one column — only the first one will be used.</div>
            <div class="im-table-wrap">
                <table class="im-table">
                    <thead><tr><th>Column in your file</th><th>Example values</th><th>Import as</th></tr></thead>
                    <tbody>
                    @foreach($import->headers as $i => $header)
                        @php $auto = ($suggested[$i]['field'] ?? null) && ($suggested[$i]['field'] === ($mapping[$i] ?? null)); @endphp
                        <tr>
                            <td><strong>{{ $header !== '' ? $header : 'Column ' . ($i + 1) }}</strong></td>
                            <td class="im-sample">{{ collect($samples)->pluck($i)->filter()->take(3)->implode(' · ') ?: '—' }}</td>
                            <td>
                                <select name="mapping[{{ $i }}]" class="im-select {{ $auto ? 'is-suggested' : '' }}" data-mapping aria-label="Import {{ $header }} as">
                                    <option value="">Don't import</option>
                                    @foreach($fields as $key => $label)
                                        <option value="{{ $key }}" @selected(($mapping[$i] ?? null) === $key)>{{ $label }}{{ ($key === 'email') ? ' (required)' : '' }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="im-muted">Required: work email, and either full name or first name. Managers are matched by email (preferred) or by unique name.</p>
        </section>

        <section class="im-card">
            <h2 class="im-card-title">Options</h2>
            <div class="im-options">
                <fieldset class="im-field">
                    <legend class="im-legend">If someone is already in your organization</legend>
                    <div class="im-radio">
                        <label><input type="radio" name="duplicates" value="skip" @checked(($options['duplicates'] ?? 'skip') === 'skip')> Skip them</label>
                        <label><input type="radio" name="duplicates" value="update" @checked(($options['duplicates'] ?? '') === 'update')> Update their details</label>
                    </div>
                    <span class="im-muted">Matched by email. Owners' and admins' roles are never changed.</span>
                </fieldset>
                <fieldset class="im-field">
                    <legend class="im-legend">Invitation emails</legend>
                    <div class="im-radio">
                        <label><input type="radio" name="send_invites" value="now" @checked(($options['send_invites'] ?? 'now') === 'now')> Send when the import finishes</label>
                        <label><input type="radio" name="send_invites" value="later" @checked(($options['send_invites'] ?? '') === 'later')> I'll send them later</label>
                    </div>
                </fieldset>
                <fieldset class="im-field">
                    <legend class="im-legend">Dates like 03/04/2024 mean</legend>
                    <div class="im-radio">
                        <label><input type="radio" name="date_format" value="dmy" @checked(($options['date_format'] ?? 'dmy') === 'dmy')> 3 April (DD/MM)</label>
                        <label><input type="radio" name="date_format" value="mdy" @checked(($options['date_format'] ?? '') === 'mdy')> 4 March (MM/DD)</label>
                    </div>
                </fieldset>
                <div class="im-field">
                    <span class="im-legend">Former employees</span>
                    <label class="im-check">
                        <input type="hidden" name="skip_inactive" value="0">
                        <input type="checkbox" name="skip_inactive" value="1" @checked($options['skip_inactive'] ?? true)> Skip rows whose status is exited, resigned or inactive
                    </label>
                </div>
            </div>
        </section>

        <div class="im-actions">
            <a class="im-link" href="{{ route('import.employees') }}">Start over</a>
            <button type="submit" class="im-btn im-btn-primary">Check the data</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/import.js') }}"></script>
@endpush
