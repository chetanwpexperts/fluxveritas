@extends('layouts.app')
@section('title', 'Edit User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Edit User</h1>
            <p class="page-subtitle">{{ $user->name }} &middot; {{ $user->email }}</p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('admin.users') }}" class="btn-secondary">← Back to Users</a>
        </div>
    </div>

    @if($errors->any())
    <div class="fv-alert fv-alert-error mb-md">
        <ul class="m-0 pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="admin-form-card">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" id="edit-user-form">
            @csrf
            @method('PATCH')

            <div class="form-section">
                <h3 class="form-section-title">Basic Information</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-input" required value="{{ old('name', $user->name) }}">
                        @error('name')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-input" required value="{{ old('email', $user->email) }}">
                        @error('email')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Join Date</label>
                        <input type="date" name="join_date" class="form-input" value="{{ old('join_date', $user->join_date?->toDateString()) }}">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Role &amp; Department</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select role...</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">No department</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reporting Manager</label>
                        <select name="reporting_manager_id" class="form-select">
                            <option value="">No manager assigned</option>
                            @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('reporting_manager_id', $user->reporting_manager_id) == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }}{{ $manager->job_title ? " ({$manager->job_title})" : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employment Type *</label>
                        <select name="employment_type" class="form-select" required>
                            @foreach(['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern', 'probation' => 'Probation'] as $val => $label)
                            <option value="{{ $val }}" {{ old('employment_type', $user->employment_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Designation &amp; Skills</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-input" value="{{ old('job_title', $user->job_title) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <select name="designation" class="form-select">
                            <option value="">Select designation...</option>
                            @foreach($designations->groupBy('category') as $category => $items)
                            <optgroup label="{{ ucfirst($category) }}">
                                @foreach($items as $d)
                                <option value="{{ $d->slug }}" {{ old('designation', $user->designation) === $d->slug ? 'selected' : '' }}>{{ $d->title }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seniority Level</label>
                        <select name="seniority_level" class="form-select">
                            <option value="">Select level...</option>
                            @foreach(['intern' => 'Intern', 'junior' => 'Junior', 'mid' => 'Mid Level', 'senior' => 'Senior', 'lead' => 'Lead', 'principal' => 'Principal', 'manager' => 'Manager', 'director' => 'Director', 'c_level' => 'C-Level'] as $val => $label)
                            <option value="{{ $val }}" {{ old('seniority_level', $user->seniority_level) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Work Location</label>
                        <select name="work_location" class="form-select">
                            <option value="onsite" {{ old('work_location', $user->work_location) === 'onsite' ? 'selected' : '' }}>Onsite</option>
                            <option value="remote" {{ old('work_location', $user->work_location) === 'remote' ? 'selected' : '' }}>Remote</option>
                            <option value="hybrid" {{ old('work_location', $user->work_location) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GitHub Username</label>
                        <input type="text" name="github_username" class="form-input" value="{{ old('github_username', $user->github_username) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Skills</label>
                        <input type="text" name="skills_input" id="skills-input" class="form-input"
                            placeholder="PHP, Laravel, React (comma separated)"
                            value="{{ old('skills_input', is_array($user->skills) ? implode(', ', $user->skills) : '') }}">
                        <p class="form-hint">Separate skills with commas</p>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Change Password</h3>
                <p class="form-hint" style="margin-bottom:16px;">Leave blank to keep current password.</p>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Min 8 characters">
                        @error('password')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-input">
                    </div>
                </div>
            </div>

        </form>

        <div class="form-actions">
            @if($user->id !== auth()->id() && !$user->hasRole('super_admin'))
            <form method="POST" action="{{ route('admin.users.delete', $user->id) }}"
                  style="margin-right:auto;"
                  onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary btn-danger-outline">Delete User</button>
            </form>
            @endif
            <a href="{{ route('admin.users') }}" class="btn-secondary">Cancel</a>
            <button type="submit" form="edit-user-form" class="btn-primary">Save Changes</button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-users.js') }}"></script>
@endpush
