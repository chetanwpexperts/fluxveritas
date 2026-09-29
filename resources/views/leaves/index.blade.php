@extends('layouts.app')
@section('title', 'My Leaves')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/leaves.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="leave-page-header">
        <div>
            <h1 class="leave-page-title">My Leaves</h1>
            <p class="leave-page-sub">{{ now()->year }} · Track and manage your leave balance</p>
        </div>
        <button onclick="toggleApplyForm()" class="leave-btn-primary">+ Apply for Leave</button>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="fv-alert fv-alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="fv-alert fv-alert-error">{{ $errors->first() }}</div>
    @endif

    {{-- Balance Cards --}}
    @if($balances->count() > 0)
    <div class="leave-balance-grid">
        @foreach($balances as $bal)
        @php
            $used  = (float) $bal->used_days;
            $total = (float) $bal->total_days;
            $avail = max(0, $total - $used - (float) $bal->pending_days);
            $pct   = $total > 0 ? min(100, round(($used / $total) * 100)) : 0;
            $colors = ['lbf-blue','lbf-amber','lbf-purple','lbf-green','lbf-red'];
            $fillColor = $colors[$loop->index % count($colors)];
        @endphp
        <div class="leave-balance-card">
            <div class="leave-balance-top">
                <span class="leave-balance-name">{{ $bal->leaveType?->name ?? '—' }}</span>
                <span class="leave-code-badge">{{ $bal->leaveType?->code ?? '—' }}</span>
            </div>
            <div class="leave-bal-big">{{ number_format($avail, 1) }}</div>
            <div class="leave-bal-label">days left · {{ $used }}/{{ $total }} used</div>
            <div class="leave-balance-bar">
                <div class="leave-balance-fill {{ $fillColor }}" style="width:{{ $pct }}%"></div>
            </div>
            @if($bal->pending_days > 0)
            <div style="font-size:11px;color:#f59e0b;margin-top:4px;">{{ $bal->pending_days }}d pending</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Apply Form --}}
    <div id="apply-form" class="leave-apply-card" style="display:none;">
        <div class="leave-apply-title">New Leave Application</div>
        <form method="POST" action="{{ route('leaves.apply') }}">
            @csrf

            <div class="leave-form-row-1">
                <div class="leave-field">
                    <label class="leave-label">Leave Type *</label>
                    <select name="leave_type_id" class="leave-input" required>
                        <option value="">Select type...</option>
                        @foreach($leaveTypes as $lt)
                        @php
                            $bal   = $balances->firstWhere('leave_type_id', $lt->id);
                            $avail = $bal
                                ? max(0, (float)$bal->total_days - (float)$bal->used_days - (float)$bal->pending_days)
                                : $lt->days_per_year;
                        @endphp
                        <option value="{{ $lt->id }}">{{ $lt->name }} ({{ number_format($avail, 1) }} days left)</option>
                        @endforeach
                    </select>
                </div>

                <div class="leave-field">
                    <label class="leave-label">From *</label>
                    <input type="date" name="from_date" id="from_date" class="leave-input"
                        min="{{ now()->toDateString() }}" required>
                </div>

                <div class="leave-field">
                    <label class="leave-label">To *</label>
                    <input type="date" name="to_date" id="to_date" class="leave-input"
                        min="{{ now()->toDateString() }}" required>
                </div>

                <div class="leave-field">
                    <label class="leave-label">Half Day</label>
                    <select name="half_day" class="leave-input">
                        <option value="none">Full Day</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                    </select>
                </div>
            </div>

            <div class="leave-field" style="margin-bottom:16px;">
                <label class="leave-label">Reason *</label>
                <textarea name="reason" class="leave-input" rows="3"
                    placeholder="Brief reason for leave..." required maxlength="500"></textarea>
            </div>

            <div id="days-preview" style="display:none;margin-bottom:12px;">
                <span class="leave-days-preview">📅 <span id="days-count">0</span> working day(s)</span>
            </div>

            <div class="leave-form-footer">
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="leave-btn-primary">Submit Application</button>
                    <button type="button" onclick="toggleApplyForm()" class="leave-btn">Cancel</button>
                </div>
            </div>
        </form>
    </div>

    {{-- My Applications --}}
    <div class="leave-section">
        <div class="leave-section-head">
            <span class="leave-section-title">My Applications</span>
            @if($applications->total() > 0)
            <span class="leave-count-badge">{{ $applications->total() }}</span>
            @endif
        </div>

        @if($applications->isEmpty())
        <div class="leave-card">
            <div class="leave-empty">
                <div class="leave-empty-icon">🌴</div>
                <div class="leave-empty-text">No leave applications yet. Click "+ Apply for Leave" to submit your first request.</div>
            </div>
        </div>
        @else
        <div class="leave-card-flush">
            <table class="leave-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $app)
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
                            <span class="leave-badge leave-badge-blue">{{ $app->leaveType?->code ?? '—' }}</span>
                            <span style="font-size:12px;color:#6b7280;margin-left:5px;">{{ $app->leaveType?->name ?? '—' }}</span>
                        </td>
                        <td style="color:#6b7280;font-size:12px;white-space:nowrap;">
                            {{ $app->from_date->format('M j') }}@if($app->from_date != $app->to_date) – {{ $app->to_date->format('M j, Y') }}@else , {{ $app->from_date->format('Y') }}@endif
                        </td>
                        <td style="font-size:13px;white-space:nowrap;">
                            {{ $app->is_half_day ? '½ day' : number_format($app->days, 0).'d' }}
                            @if($app->half_day && $app->half_day !== 'none')
                            <div style="font-size:11px;color:#9ca3af;">{{ ucfirst($app->half_day) }}</div>
                            @endif
                        </td>
                        <td style="color:#6b7280;font-size:12px;max-width:200px;">
                            {{ \Str::limit($app->reason, 50) }}
                        </td>
                        <td>
                            <span class="leave-badge {{ $badgeClass }}">{{ ucfirst($app->status) }}</span>
                            @if($app->reviewer_note)
                            <div style="font-size:11px;color:#9ca3af;margin-top:2px;">{{ \Str::limit($app->reviewer_note, 40) }}</div>
                            @endif
                        </td>
                        <td>
                            @if(in_array($app->status, ['pending','approved']) && $app->from_date->isFuture())
                            <form method="POST" action="{{ route('leaves.cancel', $app->id) }}"
                                onsubmit="return confirm('Cancel this leave?')">
                                @csrf
                                <button type="submit" class="leave-btn leave-btn-reject">Cancel</button>
                            </form>
                            @else
                            <span style="color:#d1d5db;font-size:13px;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:8px 16px 4px;">{{ $applications->links() }}</div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function toggleApplyForm() {
    var form = document.getElementById('apply-form');
    var isHidden = form.style.display === 'none';
    form.style.display = isHidden ? 'block' : 'none';
    if (isHidden) form.scrollIntoView({ behavior: 'smooth' });
}

document.getElementById('from_date')?.addEventListener('change', function() {
    var to = document.getElementById('to_date');
    if (to && (!to.value || to.value < this.value)) {
        to.value = this.value;
        to.min   = this.value;
    }
    updateDaysPreview();
});

document.getElementById('to_date')?.addEventListener('change', updateDaysPreview);

function updateDaysPreview() {
    var from = document.getElementById('from_date').value;
    var to   = document.getElementById('to_date').value;
    if (!from || !to) return;
    var days = 0, cur = new Date(from), end = new Date(to);
    while (cur <= end) {
        var day = cur.getDay();
        if (day !== 0 && day !== 6) days++;
        cur.setDate(cur.getDate() + 1);
    }
    var preview = document.getElementById('days-preview');
    document.getElementById('days-count').textContent = days;
    preview.style.display = days > 0 ? 'block' : 'none';
}
</script>
@endpush
