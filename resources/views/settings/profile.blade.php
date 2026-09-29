@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div style="display:flex;flex-direction:column;gap:20px;">

            @if(session('success'))
            <div style="background:#f0fdf4;color:#16a34a;border:1px solid rgba(22,163,74,0.2);padding:12px 16px;border-radius:8px;font-size:0.875rem;font-weight:500;">
                ✓ {{ session('success') }}
            </div>
            @endif

            {{-- Profile Info --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Profile Information</h2>
                </div>
                <form method="POST" action="{{ route('settings.profile.update') }}">
                    @csrf
                    <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Full Name <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="fv-input">
                                @error('name')<p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Email Address <span style="color:#ef4444;">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="fv-input">
                                @error('email')<p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+91 98765 43210" class="fv-input">
                                @error('phone')<p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Work Location</label>
                                <select name="work_location" class="fv-input">
                                    <option value="onsite" {{ old('work_location', $user->work_location) === 'onsite' ? 'selected' : '' }}>Onsite</option>
                                    <option value="remote" {{ old('work_location', $user->work_location) === 'remote' ? 'selected' : '' }}>Remote</option>
                                    <option value="hybrid" {{ old('work_location', $user->work_location) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                </select>
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Skills</label>
                                <input type="text" name="skills_input" id="skills-input" class="fv-input"
                                    placeholder="PHP, Laravel, React (comma separated)"
                                    value="{{ old('skills_input', is_array($user->skills) ? implode(', ', $user->skills) : '') }}">
                                <p style="font-size:0.75rem;color:#94a3b8;margin-top:4px;">Separate skills with commas</p>
                            </div>
                        </div>
                    </div>
                    <div style="padding:14px 24px;background:#fafafa;border-top:1px solid #e4e4e7;display:flex;justify-content:flex-end;">
                        <button type="submit" style="background:#18181b;color:white;padding:9px 20px;border-radius:6px;font-weight:600;font-size:0.875rem;border:none;cursor:pointer;">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- Employment Details (read-only, set by admin) --}}
            @if($user->job_title || $user->designation || $user->seniority_level || $user->employment_type || $user->reportingManager)
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;display:flex;align-items:center;gap:10px;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Employment Details</h2>
                    <span style="font-size:0.72rem;color:#94a3b8;font-weight:500;">Set by your administrator</span>
                </div>
                <div style="padding:24px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    @if($user->job_title)
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Job Title</div>
                        <div style="font-size:0.9rem;font-weight:600;color:#18181b;">{{ $user->job_title }}</div>
                    </div>
                    @endif
                    @if($user->designation)
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Designation</div>
                        <div style="font-size:0.9rem;font-weight:600;color:#18181b;">{{ $user->designation }}</div>
                    </div>
                    @endif
                    @if($user->seniority_level)
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Seniority Level</div>
                        <div>
                            <span class="au-seniority-badge au-seniority-{{ $user->seniority_level }}">
                                {{ ucfirst(str_replace('_', ' ', $user->seniority_level)) }}
                            </span>
                        </div>
                    </div>
                    @endif
                    @if($user->employment_type)
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Employment Type</div>
                        <div>
                            <span class="au-employment-badge au-emp-{{ $user->employment_type }}">
                                {{ ucfirst(str_replace('_', ' ', $user->employment_type)) }}
                            </span>
                        </div>
                    </div>
                    @endif
                    @if($user->reportingManager)
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Reporting To</div>
                        <div style="font-size:0.9rem;font-weight:600;color:#18181b;">{{ $user->reportingManager->name }}</div>
                    </div>
                    @endif
                    @if($user->join_date)
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">Joined</div>
                        <div style="font-size:0.9rem;color:#52525b;">{{ $user->join_date->format('M j, Y') }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Change Password --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Change Password</h2>
                </div>
                <form method="POST" action="{{ route('settings.password.update') }}">
                    @csrf
                    <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Current Password <span style="color:#ef4444;">*</span></label>
                            <input type="password" name="current_password" class="fv-input" class="w-full">
                            @error('current_password')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">New Password <span style="color:#ef4444;">*</span></label>
                            <input type="password" name="password" class="fv-input" class="w-full">
                            @error('password')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Confirm New Password <span style="color:#ef4444;">*</span></label>
                            <input type="password" name="password_confirmation" class="fv-input" class="w-full">
                            @error('password_confirmation')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div style="padding:14px 24px;background:#fafafa;border-top:1px solid #e4e4e7;display:flex;justify-content:flex-end;">
                        <button type="submit" style="background:#18181b;color:white;padding:9px 20px;border-radius:6px;font-weight:600;font-size:0.875rem;border:none;cursor:pointer;">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

    </div>

</div>
@endsection
