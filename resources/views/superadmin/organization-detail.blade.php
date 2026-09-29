@extends('layouts.app')

@section('title', $org->name . ' — Detail')

@section('content')
<div class="page-wrapper">

    {{-- Breadcrumb --}}
    <div class="sa-breadcrumb">
        <a href="{{ route('superadmin.organizations') }}" class="sa-breadcrumb-link">Organizations</a>
        <svg class="sa-icon-xs sa-icon-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="sa-breadcrumb-current">{{ $org->name }}</span>
    </div>

    {{-- Header --}}
    <div class="sa-org-header">
        <div>
            <div class="sa-org-header-left">
                <div class="sa-org-avatar">{{ strtoupper(substr($org->name, 0, 2)) }}</div>
                <div>
                    <div class="sa-org-name">{{ $org->name }}</div>
                    <div class="sa-org-slug">{{ $org->slug }}</div>
                </div>
                <span class="sa-status-badge sa-status-{{ $org->status ?? 'active' }}">{{ ucfirst($org->status ?? 'active') }}</span>
                <span class="sa-plan-badge sa-plan-{{ $org->plan ?? 'free' }}">{{ ucfirst($org->plan ?? 'free') }}</span>
            </div>
            @if($org->notes)
            <div class="sa-org-note"><strong>Suspension note:</strong> {{ $org->notes }}</div>
            @endif
        </div>

        {{-- Action bar --}}
        <div class="sa-org-action-bar">
            <a href="{{ route('superadmin.org.modules', $org->id) }}" class="fv-btn fv-btn-secondary">
                <svg class="sa-icon-xs" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Manage Modules
            </a>

            @if(($org->status ?? 'active') === 'pending')
            <form method="POST" action="{{ route('superadmin.organizations.approve', $org->id) }}">
                @csrf
                <button type="submit" class="fv-btn fv-btn-primary"
                        data-confirm="Approve {{ addslashes($org->name) }}?">
                    Approve Organization
                </button>
            </form>
            @endif

            <form method="POST" action="{{ route('superadmin.organizations.plan', $org->id) }}" class="sa-form-inline">
                @csrf
                <label class="sa-form-label">Plan:</label>
                <select name="plan" class="sa-plan-select" data-autosubmit>
                    @foreach(['free','pro','enterprise'] as $p)
                    <option value="{{ $p }}" {{ ($org->plan ?? 'free') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </form>

            @if(($org->status ?? 'active') !== 'suspended')
            <div x-data="{ reason: '' }">
                <form method="POST" action="{{ route('superadmin.organizations.suspend', $org->id) }}" x-ref="suspendForm">
                    @csrf
                    <input type="hidden" name="reason" x-model="reason">
                    <button type="button" class="fv-btn fv-btn-danger"
                            @click="reason=prompt('Reason for suspension (at least 10 characters, recorded in the audit log). Members will be signed out.');if(reason && reason.trim().length >= 10)$refs.suspendForm.submit();else if(reason!==null)alert('Please enter a reason of at least 10 characters.')">
                        Suspend
                    </button>
                </form>
            </div>
            @else
            <form method="POST" action="{{ route('superadmin.organizations.reactivate', $org->id) }}">
                @csrf
                <button type="submit" class="fv-btn fv-btn-primary">Reactivate</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Stats row --}}
    <div class="sa-detail-stats">
        @php $detailStats = [
            ['Members',        $orgStats['members'],    'sa-detail-stat-black'],
            ['Activities',     $orgStats['activities'], 'sa-detail-stat-violet'],
            ['Fairness Flags', $orgStats['flags'],      'sa-detail-stat-amber'],
            ['Blockers',       $orgStats['blockers'],   'sa-detail-stat-red'],
        ]; @endphp
        @foreach($detailStats as [$label, $val, $colorClass])
        <div class="sa-detail-stat">
            <div class="sa-detail-stat-label">{{ $label }}</div>
            <div class="sa-detail-stat-value {{ $colorClass }}">{{ number_format($val) }}</div>
        </div>
        @endforeach
    </div>

    {{-- Org Info --}}
    <div class="fv-card">
        <div class="fv-section-header">
            <div class="fv-section-title">Organization Details</div>
        </div>
        <div class="sa-info-grid">
            @php
            $infoRows = [
                ['Organization ID', '#' . $org->id],
                ['Slug',            $org->slug],
                ['Plan',            ucfirst($org->plan ?? 'free')],
                ['Status',          ucfirst($org->status ?? 'active')],
                ['Registered',      $org->created_at->format('M j, Y')],
                ['Approved At',     $org->approved_at ? $org->approved_at->format('M j, Y') : '—'],
            ];
            @endphp
            @foreach($infoRows as [$label, $val])
            <div>
                <div class="sa-info-label">{{ $label }}</div>
                <div class="sa-info-value">{{ $val }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Members table --}}
    <div class="fv-card">
        <div class="fv-section-header">
            <div>
                <div class="fv-section-title">Members</div>
                <div class="fv-section-sub">{{ $members->count() }} member{{ $members->count() !== 1 ? 's' : '' }} in this organization.</div>
            </div>
        </div>
        @if($members->isEmpty())
        <div class="sa-empty">
            <div class="sa-empty-title">No members yet.</div>
        </div>
        @else
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>GitHub</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="sa-th-right">Actions</th>
                </tr></thead>
                <tbody>
                    @foreach($members as $member)
                    <tr>
                        <td>
                            <div class="sa-user-cell">
                                <div class="sa-member-avatar">{{ $member['avatar'] }}</div>
                                <div class="sa-td-primary">{{ $member['name'] }}</div>
                            </div>
                        </td>
                        <td class="sa-td-muted">{{ $member['email'] }}</td>
                        <td>
                            @foreach($member['roles'] as $r)
                            <span class="sa-role-badge">{{ $r }}</span>
                            @endforeach
                        </td>
                        <td class="sa-td-muted sa-td-mono">{{ $member['github'] ?? '—' }}</td>
                        <td>
                            @if($member['is_active'])
                            <span class="sa-status-badge sa-status-active">Active</span>
                            @else
                            <span class="sa-status-badge sa-status-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="sa-td-muted">{{ $member['joined']->format('M j, Y') }}</td>
                        <td class="sa-td-actions">
                            <form method="POST" action="{{ route('superadmin.impersonate', $member['id']) }}">
                                @csrf
                                <button type="submit" class="fv-btn fv-btn-secondary"
                                        data-confirm="Impersonate {{ addslashes($member['name']) }}? You will be logged in as this user.">
                                    Impersonate
                                </button>
                            </form>
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
