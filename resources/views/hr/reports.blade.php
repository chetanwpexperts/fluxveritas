@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/leaves.css') }}">

<div class="leave-page">

  <div class="leave-page-header">
    <div>
      <h1 class="leave-page-title">HR Reports</h1>
      <p class="leave-page-sub">
        Organization overview for {{ now()->format('F Y') }}
      </p>
    </div>
  </div>

  {{-- ROW 1: HEADCOUNT STATS --}}
  <div class="leave-stats-grid" style="margin-bottom:1.5rem">
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $totalEmployees }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-blue"></span>Total Employees
      </div>
    </div>
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $newThisMonth }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-green"></span>New This Month
      </div>
    </div>
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $newThisQuarter }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-amber"></span>New This Quarter
      </div>
    </div>
    <div class="leave-stat-card">
      <div class="leave-stat-num">{{ $totalDepartments }}</div>
      <div class="leave-stat-label">
        <span class="leave-dot leave-dot-blue"></span>Departments
      </div>
    </div>
  </div>

  {{-- ROW 2: DEPT HEADCOUNT + ROLE BREAKDOWN --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;
              gap:1.5rem;margin-bottom:1.5rem">

    <div class="leave-card">
      <div class="leave-section-title" style="margin-bottom:1rem">
        Department Headcount
      </div>
      @forelse($byDepartment as $dept)
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;
                    font-size:13px;margin-bottom:4px">
          <span style="color:#18181b">{{ $dept->name }}</span>
          <span style="color:#6b7280;font-weight:500">
            {{ $dept->users_count }}
          </span>
        </div>
        <div class="leave-balance-bar">
          <div class="leave-balance-fill lbf-blue"
               style="width:{{ $totalEmployees > 0
                   ? round(($dept->users_count/$totalEmployees)*100)
                   : 0 }}%">
          </div>
        </div>
      </div>
      @empty
        <p style="font-size:13px;color:#9ca3af">No departments found.</p>
      @endforelse
    </div>

    <div class="leave-card">
      <div class="leave-section-title" style="margin-bottom:1rem">
        Roles Breakdown
      </div>
      <div style="display:flex;flex-direction:column;gap:10px">
        @php
          $roleColors = [
            'admin'     => 'leave-badge-blue',
            'hr'        => 'leave-badge-purple',
            'team_lead' => 'leave-badge-amber',
            'employee'  => 'leave-badge-gray',
            'owner'     => 'leave-badge-green',
          ];
          $roleLabels = [
            'admin'     => 'Admin',
            'hr'        => 'HR',
            'team_lead' => 'Team Lead',
            'employee'  => 'Employee',
            'owner'     => 'Owner',
          ];
        @endphp
        @foreach($byRole as $role => $count)
        <div style="display:flex;justify-content:space-between;
                    align-items:center;padding:8px 12px;
                    background:#f9fafb;border-radius:8px;
                    border:0.5px solid #f3f4f6">
          <span class="leave-badge {{ $roleColors[$role] ?? 'leave-badge-gray' }}">
            {{ $roleLabels[$role] ?? $role }}
          </span>
          <span style="font-size:14px;font-weight:500;color:#18181b">
            {{ $count }}
          </span>
        </div>
        @endforeach
      </div>
    </div>

  </div>

  {{-- ROW 3: LEAVE SUMMARY --}}
  <div class="leave-card" style="margin-bottom:1.5rem">
    <div class="leave-section-title" style="margin-bottom:1rem">
      Leave Overview — {{ now()->format('F Y') }}
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);
                gap:12px;margin-bottom:1.5rem">
      <div style="background:#f9fafb;border:0.5px solid #f3f4f6;
                  border-radius:10px;padding:1rem;text-align:center">
        <div style="font-size:24px;font-weight:500;color:#18181b">
          {{ $totalLeavesTaken }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:4px">
          Total Days Taken
        </div>
      </div>
      <div style="background:#f9fafb;border:0.5px solid #f3f4f6;
                  border-radius:10px;padding:1rem;text-align:center">
        <div style="font-size:24px;font-weight:500;color:#18181b">
          {{ $pendingApprovals }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:4px">
          Pending Approvals
        </div>
      </div>
      @foreach($leaveByType as $typeName => $days)
      <div style="background:#f9fafb;border:0.5px solid #f3f4f6;
                  border-radius:10px;padding:1rem;text-align:center">
        <div style="font-size:24px;font-weight:500;color:#18181b">
          {{ $days }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:4px">
          {{ $typeName }}
        </div>
      </div>
      @endforeach
    </div>

    {{-- Leave by dept table --}}
    @if($leaveByDept->isNotEmpty())
    <div class="leave-card-flush">
      <table class="leave-table">
        <thead>
          <tr>
            <th>Department</th>
            <th>Days Taken This Month</th>
          </tr>
        </thead>
        <tbody>
          @foreach($leaveByDept as $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="leave-balance-bar" style="width:120px">
                  <div class="leave-balance-fill lbf-amber"
                       style="width:{{ $totalLeavesTaken > 0
                           ? round(($row['taken']/$totalLeavesTaken)*100)
                           : 0 }}%">
                  </div>
                </div>
                <span style="font-size:13px;color:#6b7280">
                  {{ $row['taken'] }} days
                </span>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  {{-- ROW 4: PROFILE COMPLETENESS --}}
  <div class="leave-card" style="margin-bottom:1.5rem">
    <div class="leave-section-title" style="margin-bottom:1rem">
      Profile Completeness
    </div>

    @php
      $pct = $totalEmployees > 0
          ? round(($profilesComplete / $totalEmployees) * 100)
          : 0;
    @endphp

    <div style="margin-bottom:1rem">
      <div style="display:flex;justify-content:space-between;
                  font-size:13px;margin-bottom:6px">
        <span style="color:#18181b">
          {{ $profilesComplete }} of {{ $totalEmployees }} employees
          have complete profiles
        </span>
        <span style="color:#6b7280;font-weight:500">{{ $pct }}%</span>
      </div>
      <div class="leave-balance-bar" style="height:8px">
        <div class="leave-balance-fill lbf-green"
             style="width:{{ $pct }}%">
        </div>
      </div>
    </div>

    @if($profilesIncomplete->isNotEmpty())
    <div style="font-size:12px;color:#6b7280;margin-bottom:8px">
      Employees with incomplete profiles:
    </div>
    <div class="leave-card-flush">
      <table class="leave-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Missing</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($profilesIncomplete->take(10) as $emp)
          @php
            $missing = [];
            if (!$emp->employeeProfile?->designation) $missing[] = 'Designation';
            if (!$emp->employeeProfile?->skills)      $missing[] = 'Skills';
            if (!$emp->employeeProfile?->bio)          $missing[] = 'Bio';
          @endphp
          <tr>
            <td>
              <div class="leave-user-cell">
                <div class="leave-avatar">
                  {{ strtoupper(substr($emp->name,0,1)) }}{{ strtoupper(substr(explode(' ',$emp->name)[1]??'',0,1)) }}
                </div>
                <div class="leave-user-name">{{ $emp->name }}</div>
              </div>
            </td>
            <td style="color:#6b7280;font-size:12px">
              {{ $emp->department?->name ?? '—' }}
            </td>
            <td>
              @foreach($missing as $m)
                <span class="leave-badge leave-badge-red"
                      style="margin-right:3px">{{ $m }}</span>
              @endforeach
            </td>
            <td>
              <a href="{{ route('directory.hr-edit', $emp->id) }}"
                 class="leave-btn" style="font-size:11px;padding:3px 10px">
                Edit
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

</div>
@endsection
