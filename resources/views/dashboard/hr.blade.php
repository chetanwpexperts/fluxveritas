@extends('layouts.app')
@section('content')

<div style="padding:2rem">

  {{-- HEADER --}}
  <div style="display:flex;justify-content:space-between;
              align-items:flex-start;margin-bottom:1.5rem">
    <div>
      <h1 style="font-size:22px;font-weight:500;color:#18181b">
        Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
        {{ $user->name }} 👋
      </h1>
      <p style="font-size:13px;color:#6b7280;margin-top:4px">
        {{ now()->format('l, F j, Y') }} · HR Dashboard
      </p>
    </div>
    <a href="{{ route('directory.index') }}"
       style="background:#18181b;color:#fff;padding:8px 16px;
              border-radius:8px;font-size:13px;text-decoration:none">
      View Directory
    </a>
  </div>

  {{-- STAT CARDS --}}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);
              gap:12px;margin-bottom:1.5rem">

    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="font-size:28px;font-weight:500;color:#18181b">
        {{ $totalEmployees }}
      </div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;
                  display:flex;align-items:center;gap:5px">
        <span style="width:7px;height:7px;border-radius:50%;
                     background:#185fa5;display:inline-block"></span>
        Total Employees
      </div>
    </div>

    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="font-size:28px;font-weight:500;color:#18181b">
        {{ $pendingCount }}
      </div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;
                  display:flex;align-items:center;gap:5px">
        <span style="width:7px;height:7px;border-radius:50%;
                     background:#f59e0b;display:inline-block"></span>
        Pending Leaves
      </div>
    </div>

    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="font-size:28px;font-weight:500;color:#18181b">
        {{ $newThisMonth }}
      </div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;
                  display:flex;align-items:center;gap:5px">
        <span style="width:7px;height:7px;border-radius:50%;
                     background:#10b981;display:inline-block"></span>
        New This Month
      </div>
    </div>

    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="font-size:28px;font-weight:500;color:#18181b">
        {{ $incompleteProfiles }}
      </div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;
                  display:flex;align-items:center;gap:5px">
        <span style="width:7px;height:7px;border-radius:50%;
                     background:#ef4444;display:inline-block"></span>
        Incomplete Profiles
      </div>
    </div>

  </div>

  {{-- TWO COLUMN --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;
              gap:1.5rem;margin-bottom:1.5rem">

    {{-- PENDING LEAVES --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="display:flex;justify-content:space-between;
                  align-items:center;margin-bottom:1rem">
        <div style="font-size:14px;font-weight:500;color:#18181b">
          Pending Leave Requests
        </div>
        <a href="{{ route('leaves.hr') }}"
           style="font-size:12px;color:#6b7280;text-decoration:none">
          View all →
        </a>
      </div>

      @if($pendingLeaves->isEmpty())
        <div style="text-align:center;padding:2rem;color:#9ca3af;font-size:13px">
          ✓ No pending requests
        </div>
      @else
        <div style="display:flex;flex-direction:column;gap:10px">
          @foreach($pendingLeaves as $leave)
          <div style="display:flex;align-items:center;
                      justify-content:space-between;
                      padding:10px 12px;background:#f9fafb;
                      border-radius:8px;border:0.5px solid #f3f4f6">
            <div style="display:flex;align-items:center;gap:8px">
              @php
                $parts = explode(' ', $leave->user->name);
                $ini = strtoupper(substr($parts[0],0,1))
                     . strtoupper(substr($parts[1]??'',0,1));
              @endphp
              <div style="width:28px;height:28px;border-radius:50%;
                          background:#e6f1fb;color:#185fa5;font-size:10px;
                          font-weight:500;display:flex;align-items:center;
                          justify-content:center;flex-shrink:0">
                {{ $ini }}
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:#18181b">
                  {{ $leave->user->name }}
                </div>
                <div style="font-size:11px;color:#9ca3af">
                  {{ $leave->leaveType->name ?? '' }} ·
                  {{ \Carbon\Carbon::parse($leave->from_date)->format('M d') }}
                  @if($leave->from_date !== $leave->to_date)
                    – {{ \Carbon\Carbon::parse($leave->to_date)->format('M d') }}
                  @endif
                </div>
              </div>
            </div>
            <div style="display:flex;gap:6px">
              <form method="POST"
                    action="{{ route('leaves.approve',$leave->id) }}"
                    style="display:inline">
                @csrf
                <button style="font-size:11px;padding:4px 10px;
                               border-radius:6px;border:0.5px solid #3b6d11;
                               color:#3b6d11;background:transparent;
                               cursor:pointer">
                  ✓
                </button>
              </form>
              <form method="POST"
                    action="{{ route('leaves.reject',$leave->id) }}"
                    style="display:inline">
                @csrf
                <button style="font-size:11px;padding:4px 10px;
                               border-radius:6px;border:0.5px solid #a32d2d;
                               color:#a32d2d;background:transparent;
                               cursor:pointer">
                  ✕
                </button>
              </form>
            </div>
          </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- DEPARTMENT HEADCOUNT --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="font-size:14px;font-weight:500;color:#18181b;
                  margin-bottom:1rem">
        Department Headcount
      </div>
      @forelse($departments as $dept)
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;
                    font-size:12px;margin-bottom:4px">
          <span style="color:#18181b">{{ $dept->name }}</span>
          <span style="color:#6b7280">{{ $dept->users_count }}</span>
        </div>
        <div style="height:4px;background:#f3f4f6;border-radius:2px">
          <div style="height:100%;border-radius:2px;background:#18181b;
                      width:{{ $totalEmployees > 0 ? round(($dept->users_count/$totalEmployees)*100) : 0 }}%">
          </div>
        </div>
      </div>
      @empty
        <p style="font-size:13px;color:#9ca3af">No departments found.</p>
      @endforelse
    </div>

  </div>

  {{-- BOTTOM ROW --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

    {{-- RECENT ANNOUNCEMENTS --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="display:flex;justify-content:space-between;
                  align-items:center;margin-bottom:1rem">
        <div style="font-size:14px;font-weight:500;color:#18181b">
          Recent Announcements
        </div>
        <a href="{{ route('announcements.index') }}"
           style="font-size:12px;color:#6b7280;text-decoration:none">
          View all →
        </a>
      </div>
      @forelse($announcements as $ann)
        <div style="padding:10px 0;border-bottom:0.5px solid #f3f4f6">
          <div style="font-size:13px;font-weight:500;color:#18181b">
            {{ $ann->title }}
          </div>
          <div style="font-size:11px;color:#9ca3af;margin-top:2px">
            {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
          </div>
        </div>
      @empty
        <p style="font-size:13px;color:#9ca3af;padding:1rem 0">
          No announcements yet.
        </p>
      @endforelse
    </div>

    {{-- QUICK LINKS --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;
                border-radius:12px;padding:1.25rem">
      <div style="font-size:14px;font-weight:500;color:#18181b;
                  margin-bottom:1rem">
        Quick Actions
      </div>
      <div style="display:flex;flex-direction:column;gap:8px">
        @php
          $links = [
            ['label' => 'Employee Directory',    'route' => 'directory.index',     'icon' => '👥'],
            ['label' => 'All Leave Requests',    'route' => 'leaves.hr',           'icon' => '📋'],
            ['label' => 'Document Center',       'route' => 'documents.index',     'icon' => '📁'],
            ['label' => 'HR Reports',            'route' => 'hr.reports',          'icon' => '📊'],
            ['label' => 'Onboarding',            'route' => 'onboarding.index',    'icon' => '🎯'],
            ['label' => 'Create Announcement',   'route' => 'announcements.create','icon' => '📢'],
          ];
        @endphp
        @foreach($links as $link)
          @if(\Illuminate\Support\Facades\Route::has($link['route']))
          <a href="{{ route($link['route']) }}"
             style="display:flex;align-items:center;gap:10px;
                    padding:10px 12px;border-radius:8px;
                    border:0.5px solid #f3f4f6;text-decoration:none;
                    background:#f9fafb;color:#18181b;font-size:13px;
                    transition:background 0.15s"
             onmouseover="this.style.background='#f3f4f6'"
             onmouseout="this.style.background='#f9fafb'">
            <span>{{ $link['icon'] }}</span>
            {{ $link['label'] }}
          </a>
          @endif
        @endforeach
      </div>
    </div>

  </div>

</div>
@endsection
