@extends('layouts.app')
@section('title', 'Employee Directory')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/directory.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="dir-header">
        <div>
            <h1 class="dir-title">Employee Directory</h1>
            <p class="dir-subtitle">{{ $totalCount }} member{{ $totalCount !== 1 ? 's' : '' }} in your organization</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('directory.edit') }}" class="dir-edit-btn" style="background:#f9fafb;color:#18181b;border:1px solid #e5e7eb;">Edit My Profile</a>
            {{-- Only show links the person can actually open (the old button led HR/admins to an owner-only page) --}}
            @can('invite_members')
            <a href="{{ route('team.invite') }}" class="dir-edit-btn">+ Invite Employee</a>
            @endcan
            @if(auth()->user()->hasAnyRole(['hr','admin','owner','super_admin']))
            <a href="{{ route('import.employees') }}" class="dir-edit-btn">Import employees</a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="dir-alert">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('directory.index') }}" class="dir-filters">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by name, designation, skill..."
               class="dir-filter-input dir-filter-wide">

        <select name="dept" class="dir-filter-input">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" @selected(request('dept') == $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>

        @if(count($allSkills))
        <select name="skill" class="dir-filter-input">
            <option value="">All Skills</option>
            @foreach($allSkills as $skill)
            <option value="{{ $skill }}" @selected(request('skill') === $skill)>{{ $skill }}</option>
            @endforeach
        </select>
        @endif

        <button type="submit" class="dir-filter-btn">Search</button>

        @if(request()->hasAny(['search','dept','skill']))
        <a href="{{ route('directory.index') }}" class="dir-filter-clear">Clear filters</a>
        @endif
    </form>

    {{-- Grid --}}
    @if($users->isEmpty())
    <div class="dir-empty">
        <div style="font-size:32px;margin-bottom:10px;">👥</div>
        <div style="font-weight:500;margin-bottom:6px;">No employees match your search</div>
        @if(request()->hasAny(['search','dept','skill']))
        <a href="{{ route('directory.index') }}" class="dir-link">Clear filters</a>
        @endif
    </div>
    @else
    <div class="dir-grid">
        @foreach($users as $emp)
        @php
            $profile = $emp->employeeProfile;
            $colors     = ['#e6f1fb','#eaf3de','#faeeda','#eeedfe','#fcebeb','#e1f5ee'];
            $textColors = ['#185fa5','#3b6d11','#854f0b','#534ab7','#a32d2d','#0f6e56'];
            $idx = abs(crc32($emp->name)) % count($colors);
            $bg  = $colors[$idx];
            $tc  = $textColors[$idx];
            $skills = $profile?->skills ?? [];
        @endphp
        <a href="{{ route('directory.show', $emp) }}" class="dir-card" style="position:relative;">

            @if($isHr && $profile && !$profile->is_directory_visible)
            <span class="dir-role-badge" style="position:absolute;top:10px;right:10px;background:#f3f4f6;color:#6b7280;">Hidden</span>
            @endif

            <div class="dir-card-avatar" style="background:{{ $bg }};color:{{ $tc }};">
                @if($profile?->profile_photo)
                <img src="{{ Storage::url($profile->profile_photo) }}" class="dir-card-photo" alt="{{ $emp->name }}">
                @else
                {{ strtoupper(substr($emp->name, 0, 2)) }}
                @endif
            </div>

            <div class="dir-card-name">{{ $emp->name }}</div>
            <div class="dir-card-title">{{ $profile?->designation ?? 'No designation' }}</div>
            @if($emp->department && is_object($emp->department))
            <div class="dir-card-dept">{{ $emp->department->name ?? $emp->department ?? '' }}</div>
            @endif

            @if(count($skills))
            <div class="dir-card-roles" style="margin-top:6px;">
                @foreach(array_slice($skills, 0, 3) as $skill)
                <span class="dir-role-badge">{{ $skill }}</span>
                @endforeach
                @if(count($skills) > 3)
                <span class="dir-role-badge">+{{ count($skills) - 3 }} more</span>
                @endif
            </div>
            @endif
        </a>
        @endforeach
    </div>

    <div class="dir-pagination">{{ $users->links() }}</div>
    @endif

</div>
@endsection
