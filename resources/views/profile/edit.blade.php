<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 style="font-weight:800; font-size:1.5rem; color:var(--text); line-height:1.2;">Profile</h1>
            <p style="font-size:0.875rem; color:#475569; margin-top:4px;">Manage your account information and security settings.</p>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:32px 24px; display:flex; flex-direction:column; gap:1.5rem;">

        {{-- Profile Information --}}
        <div class="fv-card anim-fade-up delay-1">
            <div class="fv-section-header">
                <div class="fv-section-title">Profile Information</div>
                <div class="fv-section-sub">Update your account's profile information and email address.</div>
            </div>
            <div style="padding:1.5rem; max-width:560px;">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        {{-- Update Password --}}
        <div class="fv-card anim-fade-up delay-2">
            <div class="fv-section-header">
                <div class="fv-section-title">Update Password</div>
                <div class="fv-section-sub">Ensure your account is using a long, random password to stay secure.</div>
            </div>
            <div style="padding:1.5rem; max-width:560px;">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        {{-- Delete Account --}}
        <div class="fv-card anim-fade-up delay-3" style="border-color:rgba(220,38,38,0.25);">
            <div class="fv-section-header" style="border-bottom-color:rgba(220,38,38,0.2);">
                <div class="fv-section-title" style="color:#dc2626;">Delete Account</div>
                <div class="fv-section-sub">Once deleted, all resources and data will be permanently removed.</div>
            </div>
            <div style="padding:1.5rem; max-width:560px;">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>
</x-app-layout>
