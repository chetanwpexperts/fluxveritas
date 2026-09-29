<x-app-layout>
@section('title', 'Role Management')

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Role Management</h1>
        <p class="page-subtitle">All roles and their associated permissions</p>
    </div>
</div>

@include('settings.partials.tabs')

    @php
    $roleColors = [
        'super_admin' => ['bg'=>'#f4f4f5','color'=>'#18181b','border'=>'#e4e4e7','header'=>'#18181b'],
        'owner'       => ['bg'=>'#f4f4f5','color'=>'#18181b','border'=>'#e4e4e7','header'=>'#18181b'],
        'admin'       => ['bg'=>'#f4f4f5','color'=>'#3f3f46','border'=>'#e4e4e7','header'=>'#3f3f46'],
        'team_lead'   => ['bg'=>'#fafafa','color'=>'#52525b','border'=>'#e4e4e7','header'=>'#52525b'],
        'employee'    => ['bg'=>'#fafafa','color'=>'#71717a','border'=>'#e4e4e7','header'=>'#71717a'],
        'viewer'      => ['bg'=>'#fafafa','color'=>'#a1a1aa','border'=>'#e4e4e7','header'=>'#a1a1aa'],
    ];
    $categoryLabels = [
        'view'    => 'View',
        'create'  => 'Create',
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'manage'  => 'Manage',
        'sync'    => 'Sync',
        'run'     => 'Run',
        'confirm' => 'Confirm',
        'dismiss' => 'Dismiss',
        'resolve' => 'Resolve',
        'escalate'=> 'Escalate',
        'invite'  => 'Invite',
        'remove'  => 'Remove',
        'update'  => 'Update',
        'refresh' => 'Refresh',
        'query'   => 'Query',
        'access'  => 'Access',
        'export'  => 'Export',
        'impersonate' => 'Impersonate',
    ];
    @endphp


        {{-- Roles Grid --}}
        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:20px;">
            @foreach($roles as $i => $role)
            @php $rc = $roleColors[$role['name']] ?? ['bg'=>'#f8fafc','color'=>'#475569','border'=>'#e2e8f0','header'=>'#475569']; @endphp
            <div class="fv-card anim-fade-up delay-{{ min($i,3) }}" x-data="{ expanded: false }">
                <div style="padding:20px 24px; border-bottom:1px solid var(--border);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:20px;
                                         background:{{ $rc['bg'] }}; color:{{ $rc['color'] }}; border:1px solid {{ $rc['border'] }};">
                                {{ $role['label'] }}
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div class="text-center">
                                <div style="font-size:1.25rem; font-weight:800; color:var(--text);">{{ $role['users_count'] }}</div>
                                <div style="font-size:0.68rem; color:#94a3b8; white-space:nowrap;">members</div>
                            </div>
                            <div class="text-center">
                                <div style="font-size:1.25rem; font-weight:800; color:var(--text);">{{ $role['permissions_count'] }}</div>
                                <div style="font-size:0.68rem; color:#94a3b8; white-space:nowrap;">permissions</div>
                            </div>
                        </div>
                    </div>

                    {{-- Permissions chips (collapsed by default) --}}
                    <div x-show="!expanded" style="display:flex; flex-wrap:wrap; gap:5px;">
                        @foreach($role['permissions']->take(8) as $perm)
                        <span style="font-size:0.68rem; padding:2px 8px; background:#f1f5f9; color:#64748b;
                                     border:1px solid #e2e8f0; border-radius:20px;">
                            {{ str_replace('_', ' ', $perm) }}
                        </span>
                        @endforeach
                        @if($role['permissions_count'] > 8)
                        <span style="font-size:0.68rem; padding:2px 8px; background:var(--surface); color:var(--text-2);
                                     border:1px solid var(--border); border-radius:20px; cursor:pointer;"
                              @click="expanded = true">
                            +{{ $role['permissions_count'] - 8 }} more
                        </span>
                        @endif
                    </div>

                    {{-- All permissions expanded --}}
                    <div x-show="expanded" x-transition style="display:flex; flex-wrap:wrap; gap:5px;">
                        @foreach($role['permissions'] as $perm)
                        <span style="font-size:0.68rem; padding:2px 8px; background:#f1f5f9; color:#64748b;
                                     border:1px solid #e2e8f0; border-radius:20px;">
                            {{ str_replace('_', ' ', $perm) }}
                        </span>
                        @endforeach
                        <span style="font-size:0.68rem; padding:2px 8px; background:var(--surface); color:var(--text-3);
                                     border:1px solid var(--border); border-radius:20px; cursor:pointer;"
                              @click="expanded = false">
                            Collapse
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Permission Comparison Table --}}
        <div class="fv-card anim-fade-up delay-2">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Permission Matrix</div>
                    <div class="fv-section-sub">What each role can do across the system.</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Permission</th>
                            @foreach($roles as $role)
                            @php $rc = $roleColors[$role['name']] ?? ['bg'=>'#f8fafc','color'=>'#475569','border'=>'#e2e8f0']; @endphp
                            <th style="text-align:center; white-space:nowrap;">
                                <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px;
                                             background:{{ $rc['bg'] }}; color:{{ $rc['color'] }}; border:1px solid {{ $rc['border'] }};">
                                    {{ $role['label'] }}
                                </span>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allPermissions as $category => $perms)
                        <tr>
                            <td colspan="{{ $roles->count() + 1 }}"
                                style="background:var(--surface); padding:8px 16px; font-size:0.7rem; font-weight:700;
                                       color:var(--text-4); text-transform:uppercase; letter-spacing:0.06em;">
                                {{ ucfirst($category) }}
                            </td>
                        </tr>
                        @foreach($perms as $perm)
                        <tr>
                            <td style="font-size:0.8rem; color:var(--text-2);">{{ str_replace('_', ' ', $perm) }}</td>
                            @foreach($roles as $role)
                            <td class="text-center">
                                @if($role['permissions']->contains($perm))
                                    <span style="color:#16a34a; font-size:1rem;">✓</span>
                                @else
                                    <span style="color:#e2e8f0; font-size:1rem;">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

</div>
</x-app-layout>
