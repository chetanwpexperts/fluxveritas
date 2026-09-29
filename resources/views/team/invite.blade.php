<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="{{ route('team.index') }}" style="display:flex; align-items:center; justify-content:center;
               width:32px; height:32px; border-radius:8px; background:#fff; border:1px solid #e2e8f0;
               color:#64748b; text-decoration:none; flex-shrink:0;">
                <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 style="font-size:1.5rem; font-weight:800; color:#0f172a; letter-spacing:-0.02em; line-height:1.2;">Invite Member</h1>
                <p style="font-size:0.875rem; color:#94a3b8; margin-top:4px;">Send an invitation link to add someone to your organization.</p>
            </div>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div style="max-width:560px; display:flex; flex-direction:column; gap:20px;">

            {{-- Invite link result --}}
            @if(session('invite_link'))
            <div class="anim-fade-up" style="background:var(--surface); border:1px solid var(--border);
                         border-radius:12px; padding:20px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <svg style="width:18px; height:18px; color:var(--text-2); flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span style="font-weight:700; color:var(--text); font-size:0.9rem;">
                        Invite link created for {{ session('invite_email') }}
                    </span>
                </div>
                <p style="font-size:0.8rem; color:var(--text-3); margin-bottom:12px;">
                    Share this link with them. It expires in 7 days.
                </p>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="text" value="{{ session('invite_link') }}" readonly
                           id="invite-link-input"
                           style="flex:1; font-size:0.78rem; font-family:monospace; padding:9px 12px;
                                  border:1px solid var(--border); border-radius:8px;
                                  background:white; color:var(--text-2); outline:none;"
                           onclick="this.select()">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('invite-link-input').value); this.textContent='Copied!'; setTimeout(()=>{ this.textContent='Copy Link'; },2000)"
                            class="fv-btn fv-btn-primary"
                            style="white-space:nowrap; flex-shrink:0;">
                        Copy Link
                    </button>
                </div>
            </div>
            @endif

            {{-- Form --}}
            <div class="fv-card anim-fade-up {{ session('invite_link') ? 'delay-1' : '' }}">
                <div class="fv-section-header">
                    <div class="fv-section-title">Send Invitation</div>
                    <div class="fv-section-sub">They'll receive a link to join your organization.</div>
                </div>

                <form method="POST" action="{{ route('team.sendInvite') }}" style="padding:24px; display:flex; flex-direction:column; gap:20px;">
                    @csrf

                    <div>
                        <label for="email" class="fv-label">Email Address <span style="color:#dc2626;">*</span></label>
                        <input id="email" name="email" type="email" class="fv-input"
                               value="{{ old('email') }}"
                               placeholder="colleague@company.com" required autofocus>
                        @error('email')
                            <p class="fv-input-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="fv-label">Role <span style="color:#dc2626;">*</span></label>
                        <select id="role" name="role" class="fv-input">
                            <option value="employee"  {{ old('role', 'employee') === 'employee'  ? 'selected' : '' }}>Employee</option>
                            <option value="hr"        {{ old('role') === 'hr'        ? 'selected' : '' }}>HR</option>
                            <option value="team_lead" {{ old('role') === 'team_lead' ? 'selected' : '' }}>Team Lead</option>
                            <option value="manager"   {{ old('role') === 'manager'   ? 'selected' : '' }}>Manager</option>
                            <option value="admin"     {{ old('role') === 'admin'     ? 'selected' : '' }}>Admin</option>
                        </select>
                        <p class="fv-input-hint">Admins can invite/remove members. Employees have view access only.</p>
                        @error('role')
                            <p class="fv-input-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;
                                padding-top:8px; border-top:1px solid #e2e8f0; margin-top:4px;">
                        <a href="{{ route('team.index') }}" style="font-size:0.875rem; color:#64748b; text-decoration:none;"
                           onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                            Cancel
                        </a>
                        <button type="submit" class="fv-btn fv-btn-primary">
                            <svg style="width:15px; height:15px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Send Invite
                        </button>
                    </div>
                </form>
            </div>

            {{-- Bulk invite link --}}
            <div style="text-align:center; padding-top:4px;">
                <a href="{{ route('team.bulk-invite') }}"
                   style="font-size:0.8rem; color:#64748b; text-decoration:none;"
                   onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                    Inviting many people? Use Bulk Invite &rarr;
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
