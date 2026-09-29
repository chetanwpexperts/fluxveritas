<x-app-layout>
@section('title', 'User Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage roles and access for every team member</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('admin.users.create') }}" class="btn-primary">+ Add User</a>
    </div>
</div>

@include('settings.partials.tabs')

        {{-- Alerts --}}
        @if(session('success'))
        <div class="fv-alert fv-alert-success anim-fade-up">
            <svg class="fv-alert-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="fv-alert fv-alert-error anim-fade-up">
            <svg class="fv-alert-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        <div class="fv-card anim-fade-up">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">All Members</div>
                    <div class="fv-section-sub">{{ $users->count() }} {{ Str::plural('member', $users->count()) }} in your organization</div>
                </div>
            </div>

            @if($users->isEmpty())
            <div class="fv-empty">
                <div class="fv-empty-title">No team members yet</div>
            </div>
            @else
            <div class="au-table-wrap">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role &amp; Designation</th>
                            <th>Level &amp; Type</th>
                            <th>Manager</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="au-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="admin-users-list">
                        @foreach($users as $i => $user)
                        <tr class="anim-fade-up delay-{{ min($i+1,4) }}">
                            <td>
                                <div class="au-user-cell">
                                    <div class="au-avatar">{{ $user['avatar'] }}</div>
                                    <div>
                                        <div class="au-name">
                                            {{ $user['name'] }}
                                            @if($user['is_self'])
                                                <span class="au-self-tag">(you)</span>
                                            @endif
                                        </div>
                                        <div class="au-email">{{ $user['email'] }}</div>
                                        @if($user['job_title'])
                                        <div class="au-job-title">{{ $user['job_title'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="au-role-cell">
                                    @php $uniqueRoles = collect($user['roles'])->unique(); @endphp
                                    @if($uniqueRoles->isNotEmpty())
                                        @foreach($uniqueRoles as $role)
                                            <span class="fv-badge fv-badge-gray au-role-badge">
                                                {{ ucwords(str_replace('_', ' ', $role)) }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="fv-badge fv-badge-gray au-role-badge">No role</span>
                                    @endif
                                    @if($user['designation'])
                                    <span class="au-designation-tag">{{ $user['designation'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="au-level-cell">
                                    @if($user['seniority_level'])
                                    <span class="au-seniority-badge au-seniority-{{ $user['seniority_level'] }}">
                                        {{ ucfirst(str_replace('_', ' ', $user['seniority_level'])) }}
                                    </span>
                                    @endif
                                    @if($user['employment_type'])
                                    <span class="au-employment-badge au-emp-{{ $user['employment_type'] }}">
                                        {{ ucfirst(str_replace('_', ' ', $user['employment_type'])) }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="au-manager-cell">
                                {{ $user['reporting_manager'] ?? '—' }}
                            </td>
                            <td>
                                @if($user['is_active'] !== false)
                                    <span class="fv-badge fv-badge-green">Active</span>
                                @else
                                    <span class="fv-badge fv-badge-red">Inactive</span>
                                @endif
                            </td>
                            <td class="au-date-cell">
                                {{ $user['created_at']->format('M j, Y') }}
                            </td>
                            <td class="au-actions-col">
                                <div class="au-actions">
                                    {{-- Change Role --}}
                                    @if(!$user['is_self'])
                                    <form method="POST" action="{{ route('admin.users.role', $user['id']) }}" class="au-role-form">
                                        @csrf @method('PATCH')
                                        <select name="role" class="au-role-select" data-auto-submit>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->name }}"
                                                    {{ $user['roles']->contains($role->name) ? 'selected' : '' }}>
                                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>

                                    {{-- Toggle Status --}}
                                    <form method="POST" action="{{ route('admin.users.toggle', $user['id']) }}" class="au-toggle-form"
                                          data-confirm="{{ $user['is_active'] !== false ? 'Deactivate' : 'Activate' }} {{ $user['name'] }}?">
                                        @csrf @method('PATCH')
                                        @if($user['is_active'] !== false)
                                            <button type="submit" class="sa-btn sa-btn-warning">Deactivate</button>
                                        @else
                                            <button type="submit" class="sa-btn sa-btn-view">Activate</button>
                                        @endif
                                    </form>
                                    @else
                                    <span class="au-self-note">That's you</span>
                                    @endif

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.users.edit', $user['id']) }}" class="sa-btn sa-btn-view">Edit</a>

                                    {{-- Delete --}}
                                    @if(!$user['is_self'] && !($user['has_super_admin'] ?? false))
                                    <form method="POST" action="{{ route('admin.users.delete', $user['id']) }}"
                                          data-confirm="Delete {{ $user['name'] }}? This cannot be undone.">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="sa-btn sa-btn-delete">Delete</button>
                                    </form>
                                    @endif

                                    {{-- Permissions --}}
                                    <a href="{{ route('admin.permissions') }}?user={{ $user['id'] }}"
                                       class="sa-btn sa-btn-view">Permissions</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            <div id="admin-users-pagination" class="pagination-wrapper"></div>
        </div>

</div>
</x-app-layout>

@push('scripts')
<script src="{{ asset('js/admin-users-page.js') }}"></script>
<script src="{{ asset('js/admin-users.js') }}"></script>
@endpush
