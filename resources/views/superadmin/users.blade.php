@extends('layouts.app')

@section('title', 'All Users')

@push('scripts')
    <script src="{{ asset('js/sa-users-page.js') }}"></script>
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Page header --}}
    <div class="flex-between">
        <div>
            <div class="sa-page-title">All Users</div>
            <div class="sa-page-subtitle">{{ $users->count() }} user{{ $users->count() !== 1 ? 's' : '' }} across the platform</div>
        </div>
    </div>

    {{-- Search --}}
    <div class="sa-filter-bar">
        <div class="sa-filter-search-wrap">
            <svg class="sa-filter-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="text"
                   id="sa-users-search"
                   value="{{ request('search') }}"
                   placeholder="Search by name or email…"
                   class="sa-filter-search">
        </div>
    </div>

    {{-- Users table --}}
    <div class="fv-card">
        @if($users->isEmpty())
        <div class="sa-empty">
            <svg class="sa-empty-icon sa-icon-md" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            <div class="sa-empty-title">No users found</div>
            <div class="sa-empty-sub">Try adjusting your search.</div>
        </div>
        @else
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr>
                    <th>User</th>
                    <th>Organization</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="sa-th-right">Actions</th>
                </tr></thead>
                <tbody id="sa-users-list">
                    @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="sa-user-cell">
                                <div class="sa-user-avatar">{{ $user['avatar'] }}</div>
                                <div class="sa-user-name-cell">{{ $user['name'] }}</div>
                            </div>
                        </td>
                        <td class="sa-td-muted">{{ $user['email'] }}</td>
                        <td class="sa-td-muted">{{ $user['organization'] ?? '—' }}</td>
                        <td>
                            <div class="flex-wrap-gap-sm">
                                @forelse($user['roles'] as $role)
                                <span class="sa-role-badge">{{ $role }}</span>
                                @empty
                                <span class="sa-td-muted">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            @php $osta = $user['onboarding_status'] ?? 'active'; @endphp
                            @if($user['is_active'] && $osta === 'active')
                                <span class="sa-status-badge sa-status-active">Active</span>
                            @elseif($osta === 'pending')
                                <span class="sa-status-badge sa-status-pending">Pending</span>
                            @elseif($osta === 'rejected')
                                <span class="sa-status-badge sa-status-rejected">Rejected</span>
                            @else
                                <span class="sa-status-badge sa-status-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="sa-td-muted">{{ $user['created_at']->format('M j, Y') }}</td>
                        <td class="sa-td-actions">
                            <form method="POST" action="{{ route('superadmin.impersonate', $user['id']) }}">
                                @csrf
                                <button type="submit" class="fv-btn fv-btn-secondary"
                                        data-confirm="Impersonate {{ addslashes($user['name']) }}? You will be logged in as this user.">
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
        <div id="sa-users-pagination" class="pagination-wrapper"></div>
    </div>

</div>
@endsection
