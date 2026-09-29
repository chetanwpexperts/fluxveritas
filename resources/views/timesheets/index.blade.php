@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="dash-page-header-0 mb-lg">
        <div>
            <span class="dash-role-badge">Mini ERP</span>
            <h1 class="heading-xl mb-xs">Shift & Attendance Timesheets ⏱️</h1>
            <p class="text-muted-sm">Daily clock-in/out tracking and shift roster management</p>
        </div>
        <div>
            @if(!$todayTimesheet || !$todayTimesheet->clock_in)
            <form method="POST" action="{{ route('timesheets.clock-in') }}">
                @csrf
                <button type="submit" class="fv-btn bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-lg py-sm rounded-lg shadow-md">
                    ▶ Clock In Now
                </button>
            </form>
            @elseif(!$todayTimesheet->clock_out)
            <form method="POST" action="{{ route('timesheets.clock-out') }}">
                @csrf
                <button type="submit" class="fv-btn bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-lg py-sm rounded-lg shadow-md">
                    ⏹ Clock Out (Logged: {{ $todayTimesheet->clock_in }})
                </button>
            </form>
            @else
            <div class="fv-badge fv-badge-green p-sm font-bold">
                ✅ Clocked Out Today ({{ $todayTimesheet->total_hours }}h Total)
            </div>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">{{ session('success') }}</div>
    @endif

    {{-- Timesheets Table --}}
    <div class="fv-card overflow-x-auto">
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                    <th>Shift</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timesheets as $t)
                <tr>
                    <td>{{ $t->work_date }}</td>
                    <td class="font-semibold">{{ $t->user->name ?? 'User' }}</td>
                    <td>{{ $t->clock_in ?? '--:--' }}</td>
                    <td>{{ $t->clock_out ?? '--:--' }}</td>
                    <td class="font-bold">{{ $t->total_hours }}h</td>
                    <td><span class="fv-badge fv-badge-gray">{{ $t->shift_type }}</span></td>
                    <td><span class="fv-badge fv-badge-green">{{ strtoupper($t->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
