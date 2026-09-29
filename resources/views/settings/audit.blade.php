@extends('layouts.app')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div>

            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Platform Audit Logs</h2>
                        <p style="font-size:0.8rem;color:#71717a;margin-top:4px;">All platform actions across all organizations</p>
                    </div>
                    <span style="font-size:0.8rem;color:#71717a;">Last 50 entries</span>
                </div>

                @php
                $logs = App\Models\AuditLog::with('user', 'organization')
                    ->orderBy('created_at', 'desc')
                    ->take(50)
                    ->get();
                @endphp

                @if($logs->isEmpty())
                <div style="padding:48px;text-align:center;color:#71717a;font-size:0.875rem;">No audit logs yet</div>
                @else
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#fafafa;">
                            <th style="padding:10px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">Time</th>
                            <th style="padding:10px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">User</th>
                            <th style="padding:10px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">Action</th>
                            <th style="padding:10px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">Organization</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td style="padding:12px 16px;font-size:0.78rem;color:#71717a;border-bottom:1px solid #f4f4f5;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</td>
                            <td style="padding:12px 16px;font-size:0.8rem;color:#09090b;font-weight:500;border-bottom:1px solid #f4f4f5;">{{ $log->user?->name ?? 'System' }}</td>
                            <td style="padding:12px 16px;font-size:0.8rem;color:#3f3f46;border-bottom:1px solid #f4f4f5;">
                                <span style="background:#f4f4f5;padding:2px 8px;border-radius:4px;font-size:0.72rem;font-weight:600;">{{ $log->action }}</span>
                            </td>
                            <td style="padding:12px 16px;font-size:0.8rem;color:#71717a;border-bottom:1px solid #f4f4f5;">{{ $log->organization?->name ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

    </div>

</div>
@endsection
