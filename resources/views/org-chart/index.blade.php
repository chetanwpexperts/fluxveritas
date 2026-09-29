@extends('layouts.app')
@section('title', 'Organization Chart')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/org-chart.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Organization Chart</h1>
            <p class="page-subtitle">Your team structure and reporting hierarchy</p>
        </div>
        <div class="page-header-right">
            <div class="org-view-toggle">
                <button class="org-view-btn active" data-view="tree">🌳 Tree View</button>
                <button class="org-view-btn" data-view="list">📋 List View</button>
            </div>
            @if(auth()->user()->hasAnyRole(['admin', 'owner', 'super_admin']))
            <a href="{{ route('admin.users.create') }}" class="btn-primary">+ Add Member</a>
            @endif
        </div>
    </div>

    {{-- STATS ROW --}}
    <div class="org-stats-row">
        <div class="org-stat">
            <span class="org-stat-value">{{ $stats['total'] }}</span>
            <span class="org-stat-label">Total Members</span>
        </div>
        <div class="org-stat">
            <span class="org-stat-value">{{ $stats['departments'] }}</span>
            <span class="org-stat-label">Departments</span>
        </div>
        <div class="org-stat">
            <span class="org-stat-value">{{ $stats['locations'] }}</span>
            <span class="org-stat-label">Work Locations</span>
        </div>
        <div class="org-stat {{ $stats['no_manager'] > 0 ? 'org-stat-warn' : '' }}">
            <span class="org-stat-value">{{ $stats['no_manager'] }}</span>
            <span class="org-stat-label">No Manager Assigned</span>
        </div>
    </div>

    {{-- SEARCH --}}
    <div class="org-search-wrap">
        <input type="text" id="org-search" class="org-search-input"
               placeholder="Search by name, title, or department...">
    </div>

    {{-- TREE VIEW --}}
    <div id="view-tree" class="org-view-panel">
        <div class="org-tree-container" id="org-tree"></div>
    </div>

    {{-- LIST VIEW --}}
    <div id="view-list" class="org-view-panel hidden">
        <div class="org-list-card">
            <table class="fv-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th>Reports To</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="org-list-body"></tbody>
            </table>
        </div>
    </div>

</div>

{{-- Data container for JS --}}
<div id="org-data"
    data-tree="{{ json_encode($tree) }}"
    data-users="{{ json_encode($allUsers) }}"
    data-can-edit="{{ auth()->user()->hasAnyRole(['admin', 'owner', 'super_admin']) ? 'true' : 'false' }}">
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/org-chart.js') }}"></script>
@endpush
