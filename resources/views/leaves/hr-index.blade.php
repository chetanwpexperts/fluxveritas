@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leaves.css') }}">

<div class="leave-page">

  <div class="leave-page-header">
    <div>
      <h1 class="leave-page-title">Leave Management</h1>
      <p class="leave-page-sub">All employee leave applications for {{ $year }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <a href="{{ route('leaves.export') }}" class="leave-btn"
         style="padding:8px 14px;font-size:13px;text-decoration:none">
        ↓ Export CSV
      </a>
      <a href="{{ route('leaves.settings') }}" class="leave-btn"
         style="padding:8px 14px;font-size:13px;text-decoration:none">
        ⚙ Leave Settings
      </a>
    </div>
  </div>

  <div class="leave-stats-grid">
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $stats['pending'] ?? 0 }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-amber"></span>Pending
      </div>
    </div>
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $stats['approved'] ?? 0 }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-green"></span>Approved ({{ $year }})
      </div>
    </div>
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $stats['rejected'] ?? 0 }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-red"></span>Rejected ({{ $year }})
      </div>
    </div>
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $stats['on_leave'] ?? 0 }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-blue"></span>On Leave Today
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('leaves.hr') }}">
    <div class="leave-card"
         style="padding:1rem;display:flex;gap:10px;
                align-items:center;flex-wrap:wrap;margin-bottom:1.5rem">
      <input type="text" name="search" class="leave-input"
             style="flex:1;min-width:180px"
             placeholder="Search employee..."
             value="{{ request('search') }}">
      <select name="status" class="leave-input" style="width:140px">
        <option value="">All Status</option>
        <option value="pending"  {{ request('status')==='pending'  ? 'selected':'' }}>Pending</option>
        <option value="approved" {{ request('status')==='approved' ? 'selected':'' }}>Approved</option>
        <option value="rejected" {{ request('status')==='rejected' ? 'selected':'' }}>Rejected</option>
      </select>
      <select name="leave_type_id" class="leave-input" style="width:150px">
        <option value="">All Types</option>
        @foreach($leaveTypes as $type)
          <option value="{{ $type->id }}"
            {{ request('leave_type_id')==$type->id ? 'selected':'' }}>
            {{ $type->name }}
          </option>
        @endforeach
      </select>
      <select name="year" class="leave-input" style="width:100px">
        @foreach([now()->year, now()->year - 1] as $yr)
          <option value="{{ $yr }}" {{ $year == $yr ? 'selected':'' }}>
            {{ $yr }}
          </option>
        @endforeach
      </select>
      <button type="submit" class="leave-btn leave-btn-primary"
              style="width:auto;padding:8px 16px">Filter</button>
      @if(request()->hasAny(['search','status','leave_type_id']))
        <a href="{{ route('leaves.hr') }}"
           style="font-size:12px;color:#9ca3af;text-decoration:none">Clear</a>
      @endif
    </div>
  </form>

  <div class="leave-section">
    <div class="leave-section-actions">
      <div class="leave-section-head" style="margin-bottom:0">
        <span class="leave-section-title">All Applications</span>
        <span class="leave-count-badge">{{ $applications->total() }}</span>
      </div>
      <div class="leave-tabs">
        @foreach(['' => 'All', 'pending' => 'Pending',
                  'approved' => 'Approved', 'rejected' => 'Rejected']
                 as $val => $label)
          <a href="{{ request()->fullUrlWithQuery(['status' => $val]) }}"
             class="leave-tab {{ request('status', '') === $val ? 'active' : '' }}">
            {{ $label }}
          </a>
        @endforeach
      </div>
    </div>

    @if($applications->isEmpty())
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
              <th>From</th>
              <th>To</th>
              <th>Days</th>
              <th>Status</th>
              <th>Applied</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($applications as $app)
            @php
              $badgeClass = match($app->status) {
                'approved' => 'leave-badge-green',
                'rejected' => 'leave-badge-red',
                default    => 'leave-badge-amber',
              };
              $nameParts = explode(' ', $app->user?->name ?? 'Unknown');
              $initials  = strtoupper(substr($nameParts[0],0,1))
                         . strtoupper(substr($nameParts[1] ?? '',0,1));
            @endphp
            <tr>
              <td>
                <div class="leave-user-cell">
                  <div class="leave-avatar">{{ $initials }}</div>
                  <div>
                    <div class="leave-user-name">{{ $app->user?->name ?? 'Unknown' }}</div>
                    <div class="leave-user-role">
                      {{ $app->user?->department?->name ?? '' }}
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span class="leave-badge leave-badge-blue">
                  {{ $app->leaveType?->code ?? '—' }}
                </span>
              </td>
              <td>{{ \Carbon\Carbon::parse($app->from_date)->format('M d, Y') }}</td>
              <td>{{ \Carbon\Carbon::parse($app->to_date)->format('M d, Y') }}</td>
              <td>{{ $app->total_days }}</td>
              <td>
                <span class="leave-badge {{ $badgeClass }}">
                  {{ ucfirst($app->status) }}
                </span>
              </td>
              <td style="color:#9ca3af;font-size:12px">
                {{ \Carbon\Carbon::parse($app->created_at)->format('M d') }}
              </td>
              <td>
                @if($app->status === 'pending')
                  <div style="display:flex;gap:6px">
                    <form method="POST"
                          action="{{ route('leaves.approve', $app->id) }}"
                          style="display:inline">
                      @csrf
                      <button class="leave-btn leave-btn-approve">Approve</button>
                    </form>
                    <form method="POST"
                          action="{{ route('leaves.reject', $app->id) }}"
                          style="display:inline"
                          onsubmit="return confirm('Reject this leave?')">
                      @csrf
                      <button class="leave-btn leave-btn-reject">Reject</button>
                    </form>
                  </div>
                @elseif($app->status === 'approved')
                  <form method="POST"
                        action="{{ route('leaves.cancel', $app->id) }}"
                        style="display:inline">
                    @csrf
                    <button class="leave-btn leave-btn-revoke">Revoke</button>
                  </form>
                @else
                  <span style="color:#d1d5db">—</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div style="margin-top:1rem">{{ $applications->links() }}</div>
    @endif
  </div>

</div>
@endsection
