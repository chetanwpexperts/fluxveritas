@extends('layouts.app')
@section('title', 'Import Preview')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:2rem 1rem">

    <h1 style="font-size:22px;font-weight:500;color:#18181b;margin-bottom:4px">
        Import Preview
    </h1>
    <p style="font-size:13px;color:#6b7280;margin-bottom:1.5rem">
        Review before importing. Nothing has been created yet.
    </p>

    {{-- Summary counters --}}
    <div style="display:flex;gap:12px;margin-bottom:1.5rem">
        <div style="flex:1;background:#eaf3de;border:0.5px solid #c0dd97;
                    border-radius:10px;padding:1rem;text-align:center">
            <div style="font-size:24px;font-weight:500;color:#3b6d11">{{ count($valid) }}</div>
            <div style="font-size:12px;color:#3b6d11">Ready to import</div>
        </div>
        <div style="flex:1;background:#fcebeb;border:0.5px solid #f7c1c1;
                    border-radius:10px;padding:1rem;text-align:center">
            <div style="font-size:24px;font-weight:500;color:#a32d2d">{{ count($errors) }}</div>
            <div style="font-size:12px;color:#a32d2d">Rows with errors (skipped)</div>
        </div>
    </div>

    {{-- Error rows --}}
    @if(count($errors))
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;
                padding:1.25rem;margin-bottom:1.5rem">
        <div style="font-size:13px;font-weight:500;color:#a32d2d;margin-bottom:10px">
            Rows that will be skipped
        </div>
        <table style="width:100%;font-size:12px;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;color:#6b7280">
                    <th style="padding:6px">Row</th>
                    <th style="padding:6px">Name</th>
                    <th style="padding:6px">Email</th>
                    <th style="padding:6px">Problem</th>
                </tr>
            </thead>
            <tbody>
                @foreach($errors as $e)
                <tr style="border-top:0.5px solid #f3f4f6">
                    <td style="padding:6px">{{ $e['row'] }}</td>
                    <td style="padding:6px">{{ $e['data']['name'] }}</td>
                    <td style="padding:6px">{{ $e['data']['email'] }}</td>
                    <td style="padding:6px;color:#a32d2d">{{ implode('; ', $e['errors']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Valid rows --}}
    @if(count($valid))
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;
                padding:1.25rem;margin-bottom:1.5rem">
        <div style="font-size:13px;font-weight:500;color:#3b6d11;margin-bottom:10px">
            Ready to import ({{ count($valid) }})
        </div>
        <table style="width:100%;font-size:12px;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;color:#6b7280">
                    <th style="padding:6px">Name</th>
                    <th style="padding:6px">Email</th>
                    <th style="padding:6px">Role</th>
                    <th style="padding:6px">Dept</th>
                    <th style="padding:6px">Team</th>
                    <th style="padding:6px">Manager</th>
                </tr>
            </thead>
            <tbody>
                @foreach($valid as $v)
                <tr style="border-top:0.5px solid #f3f4f6">
                    <td style="padding:6px">{{ $v['name'] }}</td>
                    <td style="padding:6px">{{ $v['email'] }}</td>
                    <td style="padding:6px">{{ $v['role'] }}</td>
                    <td style="padding:6px">{{ $v['department'] ?: '—' }}</td>
                    <td style="padding:6px">{{ $v['team'] ?: '—' }}</td>
                    <td style="padding:6px">{{ $v['reporting_manager_email'] ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('import.employees.run') }}">
        @csrf
        <button type="submit"
                style="background:#18181b;color:#fff;border:none;padding:12px 24px;
                       border-radius:8px;font-size:14px;font-weight:500;cursor:pointer">
            Confirm &amp; Import {{ count($valid) }} {{ Str::plural('Employee', count($valid)) }}
        </button>
        <a href="{{ route('import.employees') }}"
           style="font-size:13px;color:#6b7280;margin-left:12px;text-decoration:none">
            Cancel
        </a>
    </form>

    @else
    <p style="font-size:13px;color:#9ca3af">
        No valid rows to import. Fix the errors and upload again.
    </p>
    <a href="{{ route('import.employees') }}"
       style="font-size:13px;color:#18181b">← Back</a>
    @endif

</div>
@endsection
