@extends('layouts.app')

@section('title', 'Organizations')

@push('scripts')
    <script src="{{ asset('js/sa-orgs-page.js') }}"></script>
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Page header --}}
    <div class="flex-between">
        <div>
            <div class="sa-page-title">All Organizations</div>
            <div class="sa-page-subtitle">{{ $organizations->count() }} organization{{ $organizations->count() !== 1 ? 's' : '' }} total</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="sa-filter-bar">
        <div class="sa-filter-search-wrap">
            <svg class="sa-filter-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="text"
                   id="sa-orgs-search"
                   value="{{ request('search') }}"
                   placeholder="Search organizations…"
                   class="sa-filter-search">
        </div>
        <div class="sa-filter-btns">
            @foreach(['all' => 'All', 'active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended'] as $val => $label)
            <button data-status-filter="{{ $val }}"
                    {{ ($currentStatus === $val || ($currentStatus === 'all' && $val === 'all')) ? 'data-active=1' : '' }}
                    class="sa-filter-btn {{ $currentStatus === $val || ($currentStatus === 'all' && $val === 'all') ? 'active' : '' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Organizations table --}}
    <div class="fv-card">
        @if($organizations->isEmpty())
        <div class="sa-empty">
            <svg class="sa-empty-icon sa-icon-md" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
            <div class="sa-empty-title">No organizations found</div>
            <div class="sa-empty-sub">Try adjusting your search or filter.</div>
        </div>
        @else
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr>
                    <th>Organization</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Members</th>
                    <th>Owner</th>
                    <th>Created</th>
                    <th class="sa-th-right">Actions</th>
                </tr></thead>
                <tbody id="sa-orgs-list">
                    @foreach($organizations as $org)
                    <tr>
                        <td>
                            <div class="sa-td-primary">{{ $org['name'] }}</div>
                            <div class="sa-td-sub sa-td-mono">{{ $org['slug'] }}</div>
                            @if($org['notes'])
                            <div class="sa-td-note">Note: {{ Str::limit($org['notes'], 50) }}</div>
                            @endif
                        </td>
                        <td><span class="sa-plan-badge sa-plan-{{ $org['plan'] }}">{{ ucfirst($org['plan']) }}</span></td>
                        <td><span class="sa-status-badge sa-status-{{ $org['status'] }}">{{ ucfirst($org['status']) }}</span></td>
                        <td class="sa-td-primary">{{ $org['members'] }}</td>
                        <td>
                            <div class="sa-td-primary">{{ $org['owner_name'] }}</div>
                            <div class="sa-td-sub">{{ $org['owner_email'] }}</div>
                        </td>
                        <td class="sa-td-muted">{{ $org['created_at']->format('M j, Y') }}</td>
                        <td class="sa-td-actions">
                            <div class="sa-td-actions-inner">
                                <a href="{{ route('superadmin.organizations.show', $org['id']) }}" class="fv-btn fv-btn-secondary">View</a>

                                @if($org['status'] === 'pending')
                                <form method="POST" action="{{ route('superadmin.organizations.approve', $org['id']) }}">
                                    @csrf
                                    <button type="submit" class="fv-btn fv-btn-primary"
                                            data-confirm="Approve {{ addslashes($org['name']) }}?">Approve</button>
                                </form>
                                @endif

                                <form method="POST" action="{{ route('superadmin.organizations.plan', $org['id']) }}">
                                    @csrf
                                    <select name="plan" class="sa-plan-select" data-autosubmit>
                                        @foreach(['free','pro','enterprise'] as $p)
                                        <option value="{{ $p }}" {{ $org['plan'] === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                        @endforeach
                                    </select>
                                </form>

                                @if($org['status'] !== 'suspended')
                                <div x-data="{ reason: '' }">
                                    <form method="POST" action="{{ route('superadmin.organizations.suspend', $org['id']) }}" x-ref="suspendForm">
                                        @csrf
                                        <input type="hidden" name="reason" x-model="reason">
                                        <button type="button" class="fv-btn fv-btn-danger"
                                                @click="reason=prompt('Reason for suspension (at least 10 characters, recorded in the audit log). Members will be signed out.');if(reason && reason.trim().length >= 10)$refs.suspendForm.submit();else if(reason!==null)alert('Please enter a reason of at least 10 characters.')">Suspend</button>
                                    </form>
                                </div>
                                @else
                                <form method="POST" action="{{ route('superadmin.organizations.reactivate', $org['id']) }}">
                                    @csrf
                                    <button type="submit" class="fv-btn fv-btn-primary">Reactivate</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        <div id="sa-orgs-pagination" class="pagination-wrapper"></div>
    </div>

</div>
@endsection
