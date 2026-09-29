<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; justify-content:space-between;">
            <div>
                <div style="font-size:0.75rem; color:#94a3b8; font-weight:500; margin-bottom:4px; letter-spacing:0.04em; text-transform:uppercase;">
                    <a href="{{ route('admin.index') }}" style="color:#94a3b8; text-decoration:none;">Admin</a>
                    <span style="margin:0 6px;">›</span>Pending Approvals
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <h1 style="font-size:1.5rem; font-weight:800; color:#0f172a; letter-spacing:-0.02em; line-height:1.2;">Pending Approvals</h1>
                    @if($pendingUsers->count() > 0)
                    <span class="fv-badge fv-badge-error">
                        {{ $pendingUsers->count() }} pending
                    </span>
                    @endif
                </div>
                <p style="font-size:0.875rem; color:#94a3b8; margin-top:4px;">Review and approve new user registrations.</p>
            </div>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px; display:flex; flex-direction:column; gap:24px;">

        @include('settings.partials.tabs')

        {{-- Alerts --}}
        @if(session('success'))
        <div class="fv-alert fv-alert-success anim-fade-up">
            <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="fv-alert fv-alert-error anim-fade-up">
            <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        <div class="fv-card anim-fade-up">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Awaiting Review</div>
                    <div class="fv-section-sub">
                        These users registered as team members and are waiting for an admin to approve their accounts.
                    </div>
                </div>
            </div>

            @if($pendingUsers->isEmpty())
            <div class="fv-empty" style="padding:64px 24px;">
                <div style="width:56px; height:56px; background:var(--surface); border:1px solid var(--border); border-radius:16px;
                            display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg style="width:28px; height:28px; color:var(--text-4);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="fv-empty-title">No pending approvals</div>
                <div class="fv-empty-desc">All user registrations have been reviewed.</div>
            </div>
            @else
            <div style="display:flex; flex-direction:column; gap:0;">
                @foreach($pendingUsers as $i => $pending)
                <div style="padding:20px 24px; {{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }} display:flex; align-items:flex-start; gap:20px; flex-wrap:wrap;">

                    {{-- User info --}}
                    <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:220px;">
                        <div style="width:42px; height:42px; border-radius:50%; background:var(--surface);
                                    border:1px solid var(--border); display:flex; align-items:center;
                                    justify-content:center; font-size:0.8rem; font-weight:800;
                                    color:var(--text-2); flex-shrink:0;">
                            {{ strtoupper(substr($pending->name, 0, 2)) }}
                        </div>
                        <div>
                            <div style="font-weight:700; color:var(--text); font-size:0.9rem;">{{ $pending->name }}</div>
                            <div style="font-size:0.78rem; color:var(--text-4); margin-top:1px;">{{ $pending->email }}</div>
                            <div style="margin-top:6px;">
                                <span class="fv-badge fv-badge-gray" style="font-size:0.7rem;">
                                    {{ $pending->onboarding_type === 'org_creator' ? 'Org Creator' : 'Team Member' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Registered date --}}
                    <div style="flex-shrink:0; text-align:right; min-width:100px;">
                        <div style="font-size:0.75rem; color:var(--text-4); margin-bottom:4px;">Registered</div>
                        <div style="font-size:0.8rem; font-weight:600; color:var(--text-2); white-space:nowrap;">
                            {{ $pending->created_at->format('M j, Y') }}
                        </div>
                        <div style="font-size:0.72rem; color:var(--text-4);">
                            {{ $pending->created_at->diffForHumans() }}
                        </div>
                    </div>

                    {{-- Approve action --}}
                    <form method="POST" action="{{ route('admin.approve', $pending->id) }}"
                          style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                        @csrf
                        <select name="role"
                                style="padding:7px 10px; border:1px solid var(--border); border-radius:8px;
                                       font-size:0.8rem; background:white; color:var(--text-2); cursor:pointer;">
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="fv-btn fv-btn-primary"
                                style="font-size:0.8rem; padding:7px 14px;"
                                onclick="return confirm('Approve {{ addslashes($pending->name) }}?')">
                            Approve
                        </button>
                    </form>

                    {{-- Reject action --}}
                    <form method="POST" action="{{ route('admin.reject', $pending->id) }}"
                          style="display:flex; align-items:center; gap:8px; flex-shrink:0;"
                          x-data="{ open: false }">
                        @csrf
                        <div x-show="open" x-transition style="display:flex; align-items:center; gap:6px;">
                            <input type="text" name="rejection_reason" placeholder="Reason for rejection…"
                                   style="padding:7px 10px; border:1px solid var(--border); border-radius:8px;
                                          font-size:0.8rem; background:white; color:var(--text-2); width:200px;">
                            <button type="submit" class="fv-btn fv-btn-danger"
                                    style="font-size:0.8rem; padding:7px 14px;">
                                Confirm
                            </button>
                        </div>
                        <button type="button" @click="open = !open"
                                class="fv-btn fv-btn-danger"
                                style="font-size:0.8rem; padding:7px 14px;"
                                x-text="open ? 'Cancel' : 'Reject'">
                            Reject
                        </button>
                    </form>

                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
