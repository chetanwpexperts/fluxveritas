<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="{{ route('projects.index') }}" style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:#fff; border:1px solid #e2e8f0; color:#64748b; text-decoration:none; flex-shrink:0;">
                <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 style="font-weight:800; font-size:1.5rem; color:var(--text); line-height:1.2;">New Project</h1>
                <p style="font-size:0.875rem; color:#475569; margin-top:2px;">Link a GitHub repository to track contributions.</p>
            </div>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div style="max-width:680px;">
            <div class="fv-card anim-fade-up">
                <div class="fv-section-header">
                    <div class="fv-section-title">Project details</div>
                    <div class="fv-section-sub">All fields except name are optional.</div>
                </div>

                <form method="POST" action="{{ route('projects.store') }}" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
                    @csrf

                    <div>
                        <label for="name" class="fv-label">Project Name <span style="color:#dc2626;">*</span></label>
                        <input id="name" name="name" type="text" class="fv-input" value="{{ old('name') }}" placeholder="e.g. OutraqHQ Backend" required autofocus>
                        <x-input-error :messages="$errors->get('name')" class="fv-input-error mt-1"/>
                    </div>

                    <div>
                        <label for="description" class="fv-label">Description</label>
                        <textarea id="description" name="description" rows="3" class="fv-input" placeholder="Optional short description…" style="resize:vertical;">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="fv-input-error mt-1"/>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div>
                            <label for="github_owner" class="fv-label">GitHub Owner</label>
                            <input id="github_owner" name="github_owner" type="text" class="fv-input" value="{{ old('github_owner') }}" placeholder="org-or-username" autocomplete="off" spellcheck="false">
                            <x-input-error :messages="$errors->get('github_owner')" class="fv-input-error mt-1"/>
                        </div>
                        <div>
                            <label for="github_repo" class="fv-label">Repository Name</label>
                            <input id="github_repo" name="github_repo" type="text" class="fv-input" value="{{ old('github_repo') }}" placeholder="repo-name" autocomplete="off" spellcheck="false">
                            <x-input-error :messages="$errors->get('github_repo')" class="fv-input-error mt-1"/>
                        </div>
                    </div>

                    <div>
                        <label for="status" class="fv-label">Status <span style="color:#dc2626;">*</span></label>
                        <select id="status" name="status" class="fv-input" class="pointer">
                            <option value="active"   {{ old('status','active') === 'active'   ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="fv-input-error mt-1"/>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.75rem; padding-top:0.75rem; border-top:1px solid #e2e8f0; margin-top:0.25rem;">
                        <a href="{{ route('projects.index') }}" style="font-size:0.875rem; color:#475569; text-decoration:none;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#475569'">Cancel</a>
                        <button type="submit" class="fv-btn fv-btn-primary">Create Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
