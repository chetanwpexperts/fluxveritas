@extends('layouts.app')
@section('title', $isHrEdit ? 'Editing: ' . $user->name . '\'s Profile' : 'Edit My Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/directory.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="dir-header">
        <div>
            <h1 class="dir-title">{{ $isHrEdit ? 'Editing: ' . $user->name . '\'s Profile' : 'Edit My Profile' }}</h1>
            <p class="dir-subtitle">{{ $isHrEdit ? 'HR / Admin edit mode' : 'Update your personal profile information' }}</p>
        </div>
        <a href="{{ route('directory.show', $user) }}" class="dir-edit-btn" style="background:#f9fafb;color:#18181b;border:1px solid #e5e7eb;">← Back to Profile</a>
    </div>

    @if($errors->any())
    <div class="dir-alert" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
        {{ $errors->first() }}
    </div>
    @endif

    @if($isHrEdit)
    <form method="POST" action="{{ route('directory.hr-update', $user) }}" enctype="multipart/form-data">
    @else
    <form method="POST" action="{{ route('directory.update') }}" enctype="multipart/form-data">
    @endif
    @csrf

    {{-- SECTION 1: Photo --}}
    <div class="dir-profile-section" style="margin-bottom:16px;">
        <div class="dir-profile-section-title" style="margin-bottom:14px;">Profile Photo</div>
        <div style="display:flex;align-items:center;gap:20px;">
            @php
                $colors     = ['#e6f1fb','#eaf3de','#faeeda','#eeedfe','#fcebeb','#e1f5ee'];
                $textColors = ['#185fa5','#3b6d11','#854f0b','#534ab7','#a32d2d','#0f6e56'];
                $idx = abs(crc32($user->name)) % count($colors);
                $bg  = $colors[$idx];
                $tc  = $textColors[$idx];
            @endphp
            <div style="width:80px;height:80px;border-radius:50%;background:{{ $bg }};color:{{ $tc }};display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;overflow:hidden;flex-shrink:0;">
                @if($profile->profile_photo)
                <img src="{{ Storage::url($profile->profile_photo) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="{{ $user->name }}">
                @else
                {{ strtoupper(substr($user->name, 0, 2)) }}
                @endif
            </div>
            <div>
                <input type="file" name="profile_photo" accept="image/*"
                       style="font-size:13px;" onchange="previewPhoto(this)">
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Max 2MB. JPG, PNG supported.</div>
            </div>
        </div>
    </div>

    {{-- SECTION 2: Basic Info --}}
    <div class="dir-profile-section" style="margin-bottom:16px;">
        <div class="dir-profile-section-title" style="margin-bottom:14px;">Basic Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">Designation</label>
                <input type="text" name="designation" class="dir-filter-input" style="width:100%;"
                       value="{{ old('designation', $profile->designation) }}" maxlength="100"
                       placeholder="e.g. Senior Developer">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">Date of Joining</label>
                <input type="date" name="date_of_joining" class="dir-filter-input" style="width:100%;"
                       value="{{ old('date_of_joining', $profile->date_of_joining?->toDateString()) }}">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">Date of Birth</label>
                <input type="date" name="date_of_birth" class="dir-filter-input" style="width:100%;"
                       value="{{ old('date_of_birth', $profile->date_of_birth?->toDateString()) }}">
            </div>
        </div>
    </div>

    {{-- SECTION 3: Contact --}}
    <div class="dir-profile-section" style="margin-bottom:16px;">
        <div class="dir-profile-section-title" style="margin-bottom:14px;">Contact</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">Phone</label>
                <input type="text" name="phone" class="dir-filter-input" style="width:100%;"
                       value="{{ old('phone', $profile->phone) }}" maxlength="20"
                       placeholder="+91-9876543210">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">Alternate Email</label>
                <input type="email" name="alternate_email" class="dir-filter-input" style="width:100%;"
                       value="{{ old('alternate_email', $profile->alternate_email) }}"
                       placeholder="backup@email.com">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">LinkedIn URL</label>
                <input type="url" name="linkedin_url" class="dir-filter-input" style="width:100%;"
                       value="{{ old('linkedin_url', $profile->linkedin_url) }}"
                       placeholder="https://linkedin.com/in/yourname">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:5px;">GitHub Username</label>
                <input type="text" name="github_username" class="dir-filter-input" style="width:100%;"
                       value="{{ old('github_username', $profile->github_username) }}"
                       placeholder="yourusername" maxlength="100">
            </div>
        </div>
    </div>

    {{-- SECTION 4: Skills --}}
    <div class="dir-profile-section" style="margin-bottom:16px;">
        <div class="dir-profile-section-title" style="margin-bottom:14px;">Skills</div>
        <div id="skills-container"
             style="display:flex;flex-wrap:wrap;gap:6px;padding:8px;border:0.5px solid #e5e7eb;border-radius:8px;min-height:42px;cursor:text"
             onclick="focusSkillInput()">
        </div>
        <input type="text" id="skillInput" placeholder="Type skill, press Enter or comma"
               style="border:none;outline:none;font-size:13px;margin-top:6px;width:100%;font-family:inherit;"
               onkeydown="handleSkillKey(event)">
        <input type="hidden" name="skills" id="skillsHidden"
               value="{{ old('skills', $profile->skills ? implode(',', $profile->skills) : '') }}">
    </div>

    {{-- SECTION 5: Bio --}}
    <div class="dir-profile-section" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div class="dir-profile-section-title" style="margin-bottom:0;">Bio</div>
            <span style="font-size:11px;color:#9ca3af;"><span id="bioCount">0</span> / 500</span>
        </div>
        <textarea name="bio" rows="4" maxlength="500"
                  style="width:100%;padding:9px 12px;border:0.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;"
                  placeholder="Tell us about yourself...">{{ old('bio', $profile->bio) }}</textarea>
    </div>

    {{-- SECTION 6: Visibility (only when editing own profile) --}}
    @if(!$isHrEdit)
    <div class="dir-profile-section" style="margin-bottom:16px;">
        <div class="dir-profile-section-title" style="margin-bottom:14px;">Directory Visibility</div>
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
            <input type="hidden" name="is_directory_visible" value="0">
            <input type="checkbox" name="is_directory_visible" value="1"
                   {{ $profile->is_directory_visible ? 'checked' : '' }}
                   style="width:16px;height:16px;accent-color:#18181b;cursor:pointer;">
            <div>
                <div style="font-size:13px;font-weight:500;color:#18181b;">Show me in Employee Directory</div>
                <div style="font-size:11px;color:#9ca3af;margin-top:2px;">If hidden, HR and admins can still see your profile.</div>
            </div>
        </label>
    </div>
    @endif

    {{-- Buttons --}}
    <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" class="dir-edit-btn">Save Profile</button>
        <a href="{{ route('directory.show', $user) }}" class="dir-edit-btn" style="background:#f9fafb;color:#18181b;border:1px solid #e5e7eb;">Cancel</a>
    </div>

    </form>

</div>

@push('scripts')
<script>
var skills = [];
var existing = document.getElementById('skillsHidden').value;
if (existing) {
    skills = existing.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
}
renderSkills();

function renderSkills() {
    var c = document.getElementById('skills-container');
    c.innerHTML = '';
    skills.forEach(function(s, i) {
        var pill = document.createElement('span');
        pill.style.cssText = 'background:#f3f4f6;color:#374151;font-size:12px;' +
            'padding:3px 10px;border-radius:999px;display:inline-flex;align-items:center;gap:6px';
        pill.innerHTML = s + '<span onclick="removeSkill(' + i + ')" ' +
            'style="cursor:pointer;color:#9ca3af;font-size:14px;line-height:1">×</span>';
        c.appendChild(pill);
    });
    document.getElementById('skillsHidden').value = skills.join(',');
}
function handleSkillKey(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        var val = e.target.value.replace(',', '').trim();
        if (val && !skills.includes(val)) { skills.push(val); renderSkills(); }
        e.target.value = '';
    }
    if (e.key === 'Backspace' && e.target.value === '' && skills.length) {
        skills.pop(); renderSkills();
    }
}
function removeSkill(i) { skills.splice(i, 1); renderSkills(); }
function focusSkillInput() { document.getElementById('skillInput').focus(); }

var ta = document.querySelector('textarea[name=bio]');
var counter = document.getElementById('bioCount');
function updateCount() { counter.textContent = ta.value.length; }
ta.addEventListener('input', updateCount);
updateCount();

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var avatars = document.querySelectorAll('.dir-profile-avatar, [style*="border-radius:50%"]');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
