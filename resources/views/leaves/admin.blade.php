@extends('layouts.app')
@section('title', 'Leave Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/leaves.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Header --}}
    <div class="leave-page-header">
        <div>
            <h1 class="leave-page-title">Leave Management</h1>
            <p class="leave-page-sub">Org-wide overview · {{ $year }}</p>
        </div>
        @if(auth()->user()->hasAnyRole(['admin','owner','ceo','super_admin']))
        <a href="{{ route('leaves.settings') }}" class="leave-btn">Leave Settings</a>
        @endif
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="fv-alert fv-alert-error">{{ $errors->first() }}</div>
    @endif

    {{-- Stats --}}
    <div class="leave-stats-grid">
        <div class="leave-stat-card">
            <div class="leave-stat-num">{{ $stats['pending'] }}</div>
            <div class="leave-stat-label"><span class="leave-dot leave-dot-amber"></span>Pending review</div>
        </div>
        <div class="leave-stat-card">
            <div class="leave-stat-num">{{ $stats['approved'] }}</div>
            <div class="leave-stat-label"><span class="leave-dot leave-dot-green"></span>Approved {{ $year }}</div>
        </div>
        <div class="leave-stat-card">
            <div class="leave-stat-num">{{ $stats['rejected'] }}</div>
            <div class="leave-stat-label"><span class="leave-dot leave-dot-red"></span>Rejected {{ $year }}</div>
        </div>
        <div class="leave-stat-card">
            <div class="leave-stat-num">{{ $stats['on_leave'] }}</div>
            <div class="leave-stat-label"><span class="leave-dot leave-dot-blue"></span>On leave today</div>
        </div>
    </div>

    {{-- Pending Approvals --}}
    <div class="leave-section">
        <div class="leave-section-head">
            <span class="leave-section-title">Pending Approval</span>
            @if($pendingLeaves->count())
            <span class="leave-count-badge">{{ $pendingLeaves->count() }}</span>
            @endif
        </div>

        @if($pendingLeaves->isEmpty())
        <div class="leave-card">
            <div class="leave-empty">
                <div class="leave-empty-icon">✓</div>
                <div class="leave-empty-text">No pending requests — all clear!</div>
            </div>
        </div>
        @else
        <div class="leave-card-flush">
            <table class="leave-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingLeaves as $app)
                    <tr>
                        <td>
                            <div class="leave-user-cell">
                                <div class="leave-avatar">{{ strtoupper(substr($app->user?->name ?? 'U', 0, 2)) }}</div>
                                <div>
                                    <div class="leave-user-name">{{ $app->user?->name ?? 'Unknown' }}</div>
                                    <div class="leave-user-role">{{ $app->user->job_title ?? $app->user->designation ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="leave-badge leave-badge-blue">{{ $app->leaveType?->code ?? '—' }}</span></td>
                        <td style="color:#6b7280;font-size:12px;white-space:nowrap;">
                            {{ $app->from_date->format('M d') }}@if($app->from_date != $app->to_date) – {{ $app->to_date->format('M d, Y') }}@else , {{ $app->from_date->format('Y') }}@endif
                        </td>
                        <td style="font-size:13px;white-space:nowrap;">
                            {{ $app->is_half_day ? '½ day' : number_format($app->days, 0).'d' }}
                        </td>
                        <td style="color:#6b7280;font-size:12px;max-width:180px;">
                            {{ \Str::limit($app->reason, 45) }}
                        </td>
                        <td>
                            <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;">
                                <form method="POST" action="{{ route('leaves.approve', $app->id) }}" style="display:flex;gap:4px;align-items:center;">
                                    @csrf
                                    <input type="text" name="reviewer_note" class="leave-note-input" placeholder="Note…" maxlength="300">
                                    <button type="submit" class="leave-btn leave-btn-approve">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('leaves.reject', $app->id) }}">
                                    @csrf
                                    <button type="submit" class="leave-btn leave-btn-reject">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- All Applications --}}
    <div class="leave-section">
        <div class="leave-section-head">
            <span class="leave-section-title">All Applications</span>
            <span class="leave-count-badge">{{ $allLeaves->total() }}</span>
        </div>

        @if($allLeaves->isEmpty())
        <div class="leave-card">
            <div class="leave-empty">
                <div class="leave-empty-icon">📋</div>
                <div class="leave-empty-text">No leave applications found.</div>
            </div>
        </div>
        @else
        <div class="leave-card-flush">
            <table class="leave-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Reviewer note</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allLeaves as $app)
                    @php
                        $badgeClass = match($app->status) {
                            'approved'  => 'leave-badge-green',
                            'rejected'  => 'leave-badge-red',
                            'cancelled' => 'leave-badge-gray',
                            default     => 'leave-badge-amber',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="leave-user-cell">
                                <div class="leave-avatar">{{ strtoupper(substr($app->user?->name ?? 'U', 0, 2)) }}</div>
                                <div>
                                    <div class="leave-user-name">{{ $app->user?->name ?? 'Unknown' }}</div>
                                    <div class="leave-user-role">{{ $app->user->job_title ?? $app->user->designation ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="leave-badge leave-badge-blue">{{ $app->leaveType?->code ?? '—' }}</span></td>
                        <td style="color:#6b7280;font-size:12px;white-space:nowrap;">
                            {{ $app->from_date->format('M d') }}@if($app->from_date != $app->to_date) – {{ $app->to_date->format('M d') }}@endif
                        </td>
                        <td style="font-size:13px;white-space:nowrap;">
                            {{ $app->is_half_day ? '½ day' : number_format($app->days, 0).'d' }}
                        </td>
                        <td><span class="leave-badge {{ $badgeClass }}">{{ ucfirst($app->status) }}</span></td>
                        <td style="color:#6b7280;font-size:12px;max-width:160px;">
                            {{ $app->reviewer_note ? \Str::limit($app->reviewer_note, 40) : '—' }}
                        </td>
                        <td style="color:#9ca3af;font-size:12px;white-space:nowrap;">{{ $app->created_at->format('M d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:8px 16px 4px;">{{ $allLeaves->links() }}</div>
        @endif
    </div>

</div>
@endsection
