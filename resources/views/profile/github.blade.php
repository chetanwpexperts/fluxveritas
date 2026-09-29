<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 style="font-weight:800; font-size:1.5rem; color:var(--text); line-height:1.2;">GitHub Username</h1>
            <p style="font-size:0.875rem; color:#475569; margin-top:4px;">Connect your GitHub identity for contribution tracking.</p>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div style="max-width:560px;">
            <div class="fv-card anim-fade-up">
                <div class="fv-section-header">
                    <div class="fv-section-title">Connect GitHub account</div>
                    <div class="fv-section-sub">Used to fetch your commits and pull requests from linked projects.</div>
                </div>

                <form method="POST" action="{{ route('profile.github.update') }}" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
                    @csrf
                    @method('PATCH')

                    @if(session('status') === 'github-updated')
                        <div class="fv-alert fv-alert-success">
                            <svg style="width:16px; height:16px; flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            GitHub username saved successfully.
                        </div>
                    @endif

                    <div>
                        <label for="github_username" class="fv-label">GitHub Username</label>
                        <div style="display:flex;">
                            <span class="fv-input-prefix">github.com/</span>
                            <input
                                id="github_username"
                                name="github_username"
                                type="text"
                                class="fv-input fv-input-prefixed"
                                style="border-radius:0 8px 8px 0 !important; flex:1;"
                                value="{{ old('github_username', $user->github_username) }}"
                                placeholder="your-username"
                                autocomplete="off"
                                spellcheck="false"
                            >
                        </div>
                        <div class="fv-input-hint">Alphanumeric and hyphens only, max 39 characters.</div>
                        <x-input-error :messages="$errors->get('github_username')" class="fv-input-error mt-1"/>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.75rem; padding-top:0.75rem; border-top:1px solid #e2e8f0;">
                        <a href="{{ route('dashboard') }}" style="font-size:0.875rem; color:#475569; text-decoration:none;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#475569'">Cancel</a>
                        <button type="submit" class="fv-btn fv-btn-primary">Save Username</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
