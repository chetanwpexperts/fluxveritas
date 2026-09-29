@extends('layouts.app')

@section('title', 'HR Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hr-management.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="hrm-header">
        <div>
            <h1 class="hrm-title">HR Management</h1>
            <p class="hrm-subtitle">Assign and manage HR access for your organization</p>
        </div>
    </div>

    @if(session('success'))
        <div class="hrm-alert hrm-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Info banner --}}
    <div class="hrm-info-banner">
        <span class="hrm-info-icon">&#9432;</span>
        <span>HR members can view all employee profiles, manage leave requests, create onboarding checklists, and access HR reports. They <strong>do not</strong> gain admin control — org settings and billing remain restricted to Admins and Owners.</span>
    </div>

    {{-- Current HR Members --}}
    <div class="hrm-section">
        <h2 class="hrm-section-title">Current HR Members <span class="hrm-count-badge">{{ $hrMembers->count() }}</span></h2>

        @if($hrMembers->isEmpty())
            <div class="hrm-empty">No HR members assigned yet. Assign employees below to grant HR access.</div>
        @else
            <div class="hrm-members-grid">
                @foreach($hrMembers as $member)
                <div class="hrm-member-card">
                    <div class="hrm-member-avatar">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                    <div class="hrm-member-info">
                        <div class="hrm-member-name">{{ $member->name }}</div>
                        <div class="hrm-member-meta">{{ $member->job_title ?? $member->designation ?? 'Employee' }}</div>
                        <div class="hrm-member-dept">{{ $member->department?->name ?? 'No Department' }}</div>
                    </div>
                    <form method="POST" action="{{ route('hr.revoke') }}" class="hrm-revoke-form">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $member->id }}">
                        <button type="submit" class="hrm-revoke-btn" onclick="return confirm('Revoke HR access from {{ $member->name }}?')">Revoke</button>
                    </form>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Assign HR --}}
    <div class="hrm-section">
        <h2 class="hrm-section-title">Assign HR Access</h2>
        <p class="hrm-section-desc">Select one or more employees to grant HR role. Search by name or department.</p>

        <div class="hrm-search-bar">
            <input type="text" id="empSearch" placeholder="Search employees..." class="hrm-search-input" autocomplete="off">
        </div>

        <form method="POST" action="{{ route('hr.assign') }}" id="assignForm">
            @csrf
            <div class="hrm-employees-grid" id="empGrid">
                @foreach($employees as $emp)
                <label class="hrm-emp-card" data-name="{{ strtolower($emp->name) }}" data-dept="{{ strtolower($emp->department?->name ?? '') }}">
                    <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}" class="hrm-emp-check">
                    <div class="hrm-emp-avatar">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                    <div class="hrm-emp-info">
                        <div class="hrm-emp-name">{{ $emp->name }}</div>
                        <div class="hrm-emp-role">{{ $emp->job_title ?? $emp->designation ?? 'Employee' }}</div>
                        <div class="hrm-emp-dept">{{ $emp->department?->name ?? 'No Dept' }}</div>
                    </div>
                </label>
                @endforeach

                @if($employees->isEmpty())
                    <div class="hrm-empty" id="empEmpty">All active employees already have HR access.</div>
                @endif
            </div>

            <div id="noResults" class="hrm-empty" style="display:none;">No employees match your search.</div>

            @error('user_ids')
                <div class="hrm-error">{{ $message }}</div>
            @enderror

            <div class="hrm-assign-footer">
                <span id="selectedCount" class="hrm-selected-count">0 selected</span>
                <button type="submit" class="hrm-assign-btn" id="assignBtn" disabled>Grant HR Access</button>
            </div>
        </form>
    </div>

</div>

<script>
const search  = document.getElementById('empSearch');
const grid    = document.getElementById('empGrid');
const cards   = grid ? grid.querySelectorAll('.hrm-emp-card') : [];
const noRes   = document.getElementById('noResults');
const counter = document.getElementById('selectedCount');
const assignBtn = document.getElementById('assignBtn');

function updateCount() {
    const n = document.querySelectorAll('.hrm-emp-check:checked').length;
    counter.textContent = n + ' selected';
    assignBtn.disabled  = n === 0;
}

search && search.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let visible = 0;
    cards.forEach(card => {
        const match = !q || card.dataset.name.includes(q) || card.dataset.dept.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    noRes.style.display = visible === 0 && q ? 'block' : 'none';
});

document.querySelectorAll('.hrm-emp-check').forEach(cb => cb.addEventListener('change', updateCount));
</script>
@endsection
