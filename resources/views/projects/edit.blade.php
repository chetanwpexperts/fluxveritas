<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="{{ route('projects.show', $project->id) }}" style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:#fff; border:1px solid #e2e8f0; color:#64748b; text-decoration:none; flex-shrink:0;">
                <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 style="font-weight:800; font-size:1.5rem; color:var(--text); line-height:1.2;">Edit Project</h1>
                <p style="font-size:0.875rem; font-family:monospace; color:#475569; margin-top:2px;">{{ $project->name }}</p>
            </div>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div style="max-width:680px; display:flex; flex-direction:column; gap:1.25rem;">

            <div class="fv-card anim-fade-up">
                <div class="fv-section-header">
                    <div class="fv-section-title">Project details</div>
                    <div class="fv-section-sub">Update name, repository, or status.</div>
                </div>

                <form method="POST" action="{{ route('projects.update', $project->id) }}" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="fv-label">Project Name <span style="color:#dc2626;">*</span></label>
                        <input id="name" name="name" type="text" class="fv-input" value="{{ old('name', $project->name) }}" required autofocus>
                        <x-input-error :messages="$errors->get('name')" class="fv-input-error mt-1"/>
                    </div>

                    <div>
                        <label for="description" class="fv-label">Description</label>
                        <textarea id="description" name="description" rows="3" class="fv-input" style="resize:vertical;">{{ old('description', $project->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="fv-input-error mt-1"/>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div>
                            <label for="github_owner" class="fv-label">GitHub Owner</label>
                            <input id="github_owner" name="github_owner" type="text" class="fv-input" value="{{ old('github_owner', $project->github_owner) }}" placeholder="org-or-username" autocomplete="off" spellcheck="false">
                            <x-input-error :messages="$errors->get('github_owner')" class="fv-input-error mt-1"/>
                        </div>
                        <div>
                            <label for="github_repo" class="fv-label">Repository Name</label>
                            <input id="github_repo" name="github_repo" type="text" class="fv-input" value="{{ old('github_repo', $project->github_repo) }}" placeholder="repo-name" autocomplete="off" spellcheck="false">
                            <x-input-error :messages="$errors->get('github_repo')" class="fv-input-error mt-1"/>
                        </div>
                    </div>

                    <div>
                        <label for="status" class="fv-label">Status <span style="color:#dc2626;">*</span></label>
                        <select id="status" name="status" class="fv-input" class="pointer">
                            <option value="active"   {{ old('status', $project->status) === 'active'   ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $project->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="archived" {{ old('status', $project->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="fv-input-error mt-1"/>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.75rem; padding-top:0.75rem; border-top:1px solid #e2e8f0; margin-top:0.25rem;">
                        <a href="{{ route('projects.show', $project->id) }}" style="font-size:0.875rem; color:#475569; text-decoration:none;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#475569'">Cancel</a>
                        <button type="submit" class="fv-btn fv-btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            {{-- Danger zone --}}
            <div class="fv-card anim-fade-up delay-2" style="border-color:rgba(220,38,38,0.25);">
                <div class="fv-section-header" style="border-bottom-color:rgba(220,38,38,0.2);">
                    <div class="fv-section-title" style="color:#dc2626;">Danger Zone</div>
                    <div class="fv-section-sub">This action cannot be undone.</div>
                </div>
                <div style="padding:1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:0.875rem; font-weight:600; color:#0f172a;">Delete this project</div>
                        <div style="font-size:0.8125rem; color:#475569; margin-top:2px;">Permanently removes the project and all its activity data.</div>
                    </div>
                    <form method="POST" action="{{ route('projects.destroy', $project->id) }}" onsubmit="return confirm('Delete &quot;{{ addslashes($project->name) }}&quot;? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="fv-btn fv-btn-danger" style="font-size:0.8125rem; padding:0.375rem 0.875rem;">
                            <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Delete Project
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
