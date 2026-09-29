<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="{{ route('team.invite') }}" style="display:flex; align-items:center; justify-content:center;
               width:32px; height:32px; border-radius:8px; background:#fff; border:1px solid #e2e8f0;
               color:#64748b; text-decoration:none; flex-shrink:0;">
                <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 style="font-size:1.5rem; font-weight:800; color:#0f172a; letter-spacing:-0.02em; line-height:1.2;">Bulk Invite</h1>
                <p style="font-size:0.875rem; color:#94a3b8; margin-top:4px;">Invite multiple people at once by pasting their emails.</p>
            </div>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div style="max-width:600px; display:flex; flex-direction:column; gap:20px;">

            @if(session('success'))
            <div class="anim-fade-up" style="background:#f0fdf4; border:1px solid #bbf7d0;
                         border-radius:12px; padding:16px 20px; font-size:0.875rem; color:#166534;">
                {{ session('success') }}
            </div>
            @endif

            @if(session('warning'))
            <div class="anim-fade-up" style="background:#fffbeb; border:1px solid #fde68a;
                         border-radius:12px; padding:16px 20px; font-size:0.875rem; color:#92400e;">
                {{ session('warning') }}
            </div>
            @endif

            <div class="fv-card anim-fade-up">
                <div class="fv-section-header">
                    <div class="fv-section-title">Bulk Send Invitations</div>
                    <div class="fv-section-sub">Paste emails separated by commas, spaces, or new lines.</div>
                </div>

                <form method="POST" action="{{ route('team.sendBulkInvite') }}" style="padding:24px; display:flex; flex-direction:column; gap:20px;">
                    @csrf

                    <div>
                        <label for="emails" class="fv-label">Email Addresses <span style="color:#dc2626;">*</span></label>
                        <textarea id="emails" name="emails" rows="6" class="fv-input"
                                  placeholder="alice@company.com, bob@company.com&#10;carol@company.com"
                                  style="resize:vertical; font-family:monospace; font-size:0.85rem;"
                                  required>{{ old('emails') }}</textarea>
                        <p class="fv-input-hint">Separate emails with commas, semicolons, spaces, or new lines.</p>
                        @error('emails')
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
                        <p class="fv-input-hint">All invitees will be assigned the same role.</p>
                        @error('role')
                            <p class="fv-input-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;
                                padding-top:8px; border-top:1px solid #e2e8f0; margin-top:4px;">
                        <a href="{{ route('team.invite') }}" style="font-size:0.875rem; color:#64748b; text-decoration:none;"
                           onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                            Cancel
                        </a>
                        <button type="submit" class="fv-btn fv-btn-primary">
                            <svg style="width:15px; height:15px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Send Invitations
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
