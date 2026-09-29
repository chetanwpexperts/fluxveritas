@extends('layouts.app')
@section('title', $user->name . ' — Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/directory.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    @if(session('success'))
    <div class="dir-alert">{{ session('success') }}</div>
    @endif

    <div class="dir-profile">

        {{-- LEFT SIDEBAR --}}
        <div class="dir-profile-left">
            @php
                $profile    = $user->employeeProfile;
                $colors     = ['#e6f1fb','#eaf3de','#faeeda','#eeedfe','#fcebeb','#e1f5ee'];
                $textColors = ['#185fa5','#3b6d11','#854f0b','#534ab7','#a32d2d','#0f6e56'];
                $idx = abs(crc32($user->name)) % count($colors);
                $bg  = $colors[$idx];
                $tc  = $textColors[$idx];
            @endphp

            <div class="dir-profile-avatar" style="background:{{ $bg }};color:{{ $tc }};">
                @if($profile?->profile_photo)
                <img src="{{ Storage::url($profile->profile_photo) }}" class="dir-profile-photo" alt="{{ $user->name }}">
                @else
                {{ strtoupper(substr($user->name, 0, 2)) }}
                @endif
            </div>

            <div class="dir-profile-name">{{ $user->name }}</div>
            <div class="dir-profile-title">{{ $profile?->designation ?? 'No designation' }}</div>
            @if($user->department)
            <div class="dir-profile-dept">{{ $user->department?->name ?? '' }}</div>
            @endif

            <div class="dir-profile-roles">
                @foreach($user->roles as $role)
                <span class="dir-role-badge">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                @endforeach
            </div>

            <div style="display:flex;flex-direction:column;gap:6px;margin-top:12px;">
                @if(auth()->id() === $user->id)
                <a href="{{ route('directory.edit') }}" class="dir-edit-own-btn">Edit My Profile</a>
                @endif

                @if(auth()->id() !== $user->id && auth()->user()->hasAnyRole(['hr','admin','owner']))
                <a href="{{ route('directory.hr-edit', $user) }}" class="dir-edit-own-btn" style="background:#f9fafb;color:#18181b;border:1px solid #e5e7eb;">Edit as HR</a>
                @endif
            </div>

            <div style="margin-top:16px;text-align:left;">
                <a href="{{ route('directory.index') }}" style="font-size:12px;color:#9ca3af;text-decoration:none;">← Back to Directory</a>
            </div>
        </div>

        {{-- RIGHT MAIN --}}
        <div class="dir-profile-right">

            {{-- About --}}
            <div class="dir-profile-section">
                <div class="dir-profile-section-title">About</div>
                @if($profile?->bio)
                <p style="font-size:14px;color:#374151;line-height:1.6;margin:0;">{{ $profile->bio }}</p>
                @else
                <p style="font-size:13px;color:#9ca3af;font-style:italic;margin:0;">No bio added yet.</p>
                @endif
            </div>

            {{-- Contact Information --}}
            <div class="dir-profile-section">
                <div class="dir-profile-section-title">Contact Information</div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <span style="color:#9ca3af;width:16px;text-align:center;">✉</span>
                        <a href="mailto:{{ $user->email }}" style="color:#185fa5;text-decoration:none;">{{ $user->email }}</a>
                    </div>

                    @if($canSeePrivate)
                    @if($profile?->phone)
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <span style="color:#9ca3af;width:16px;text-align:center;">📞</span>
                        <span>{{ $profile->phone }}</span>
                    </div>
                    @endif
                    @if($profile?->alternate_email)
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <span style="color:#9ca3af;width:16px;text-align:center;">✉</span>
                        <a href="mailto:{{ $profile->alternate_email }}" style="color:#185fa5;text-decoration:none;">{{ $profile->alternate_email }}</a>
                        <span style="font-size:11px;color:#9ca3af;">(alternate)</span>
                    </div>
                    @endif
                    @else
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#9ca3af;">
                        <span style="width:16px;text-align:center;">📞</span>
                        <span>••••••••••</span>
                    </div>
                    @endif

                    @if($profile?->linkedin_url)
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <span style="color:#9ca3af;width:16px;text-align:center;">in</span>
                        <a href="{{ $profile->linkedin_url }}" target="_blank" style="color:#185fa5;text-decoration:none;">LinkedIn Profile</a>
                    </div>
                    @endif

                    @if($profile?->github_username)
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <span style="color:#9ca3af;width:16px;text-align:center;">⌥</span>
                        <a href="https://github.com/{{ $profile->github_username }}" target="_blank" style="color:#185fa5;text-decoration:none;">{{ $profile->github_username }}</a>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Work Details --}}
            <div class="dir-profile-section">
                <div class="dir-profile-section-title">Work Details</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">Department</div>
                        <div style="font-size:13px;font-weight:500;">{{ $user->department?->name ?? '—' }}</div>
                    </div>
                    @if($user->reportingManager)
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">Reporting Manager</div>
                        <div style="font-size:13px;font-weight:500;">
                            <a href="{{ route('directory.show', $user->reportingManager) }}" class="dir-link">{{ $user->reportingManager->name }}</a>
                        </div>
                    </div>
                    @endif
                    @if($canSeePrivate && $profile?->date_of_joining)
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">Date of Joining</div>
                        <div style="font-size:13px;font-weight:500;">{{ $profile->date_of_joining->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($canSeePrivate && auth()->user()->hasAnyRole(['hr','owner']) && $profile?->date_of_birth)
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">Date of Birth</div>
                        <div style="font-size:13px;font-weight:500;">{{ $profile->date_of_birth->format('d M Y') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Skills --}}
            <div class="dir-profile-section">
                <div class="dir-profile-section-title">Skills</div>
                @if($profile?->skills && count($profile->skills))
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($profile->skills as $skill)
                    <span class="dir-role-badge" style="font-size:12px;padding:4px 10px;">{{ $skill }}</span>
                    @endforeach
                </div>
                @else
                <div style="font-size:13px;color:#9ca3af;font-style:italic;">No skills added yet.</div>
                @if(auth()->id() === $user->id)
                <a href="{{ route('directory.edit') }}" class="dir-link" style="font-size:13px;margin-top:8px;display:inline-block;">+ Add skills</a>
                @endif
                @endif
            </div>

        </div>{{-- end right --}}
    </div>{{-- end dir-profile --}}

</div>
@endsection
