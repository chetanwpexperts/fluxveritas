<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 style="font-weight:800; font-size:1.5rem; color:var(--text); line-height:1.2;">Create Organization</h1>
            <p style="font-size:0.875rem; color:#475569; margin-top:4px;">Set up your workspace. You'll be assigned as admin.</p>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div style="max-width:560px;">

            @if(session('success') || auth()->user()->onboarding_type === 'org_creator')
            <div style="background:var(--surface); border:1px solid var(--border); border-radius:12px;
                        padding:16px 20px; margin-bottom:24px; display:flex; gap:12px; align-items:flex-start;">
                <svg style="width:20px; height:20px; color:var(--text-2); flex-shrink:0; margin-top:1px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <div style="font-size:0.875rem; font-weight:700; color:var(--text); margin-bottom:2px;">Welcome to OutraqHQ!</div>
                    <div style="font-size:0.8rem; color:var(--text-3); line-height:1.5;">
                        You are just one step away from getting started. Set up your organization below and you will be taken straight to your dashboard.
                    </div>
                </div>
            </div>
            @endif
            <div class="fv-card anim-fade-up"
                x-data="{
                    name: '{{ old('name') }}',
                    slug: '{{ old('slug') }}',
                    slugEdited: {{ old('slug') ? 'true' : 'false' }},
                    updateSlug() {
                        if (!this.slugEdited) {
                            this.slug = this.name
                                .toLowerCase()
                                .replace(/[^a-z0-9\s-]/g, '')
                                .trim()
                                .replace(/[\s]+/g, '-');
                        }
                    }
                }"
            >
                <div class="fv-section-header">
                    <div class="fv-section-title">Organization details</div>
                    <div class="fv-section-sub">Team members can be invited after setup.</div>
                </div>

                <form method="POST" action="{{ route('organization.store') }}" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
                    @csrf

                    <div>
                        <label for="name" class="fv-label">Organization Name <span style="color:#dc2626;">*</span></label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            class="fv-input"
                            x-model="name"
                            @input="updateSlug()"
                            value="{{ old('name') }}"
                            placeholder="e.g. Acme Corp"
                            required
                            autofocus
                        >
                        <x-input-error :messages="$errors->get('name')" class="fv-input-error mt-1"/>
                    </div>

                    <div>
                        <label for="slug" class="fv-label">Slug <span style="color:#dc2626;">*</span></label>
                        <div style="display:flex;">
                            <span class="fv-input-prefix">outraqhq.app/</span>
                            <input
                                id="slug"
                                name="slug"
                                type="text"
                                class="fv-input fv-input-prefixed"
                                style="border-radius:0 8px 8px 0 !important; flex:1; font-family:monospace;"
                                x-model="slug"
                                @input="slugEdited = true"
                                value="{{ old('slug') }}"
                                placeholder="acme-corp"
                                autocomplete="off"
                                spellcheck="false"
                                required
                            >
                        </div>
                        <div class="fv-input-hint">Auto-generated. Lowercase letters, numbers, hyphens only. Must be unique.</div>
                        <x-input-error :messages="$errors->get('slug')" class="fv-input-error mt-1"/>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.75rem; padding-top:0.75rem; border-top:1px solid #e2e8f0;">
                        <a href="{{ route('dashboard') }}" style="font-size:0.875rem; color:#475569; text-decoration:none;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#475569'">Cancel</a>
                        <button type="submit" class="fv-btn fv-btn-primary">Create Organization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
