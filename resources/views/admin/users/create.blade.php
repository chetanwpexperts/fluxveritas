@extends('layouts.app')
@section('title', 'Add New User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Add New User</h1>
            <p class="page-subtitle">Create a new team member for your organization</p>
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
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="form-section">
                <h3 class="form-section-title">Basic Information</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-input" required value="{{ old('name') }}" placeholder="John Smith">
                        @error('name')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-input" required value="{{ old('email') }}" placeholder="john@company.com">
                        @error('email')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-input" required placeholder="Min 8 characters">
                        @error('password')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" placeholder="+91 98765 43210">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Join Date</label>
                        <input type="date" name="join_date" class="form-input" value="{{ old('join_date', date('Y-m-d')) }}">
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
                            <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                            @endforeach
                        </select>
                        @error('role')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">No department</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
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
                            <option value="{{ $manager->id }}" {{ old('reporting_manager_id') == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }}{{ $manager->job_title ? " ({$manager->job_title})" : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employment Type *</label>
                        <select name="employment_type" class="form-select" required>
                            @foreach(['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern', 'probation' => 'Probation'] as $val => $label)
                            <option value="{{ $val }}" {{ old('employment_type', 'full_time') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
                        <input type="text" name="job_title" class="form-input" value="{{ old('job_title') }}" placeholder="e.g. Senior Backend Developer">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <select name="designation" class="form-select">
                            <option value="">Select designation...</option>
                            @foreach($designations->groupBy('category') as $category => $items)
                            <optgroup label="{{ ucfirst($category) }}">
                                @foreach($items as $d)
                                <option value="{{ $d->slug }}" {{ old('designation') === $d->slug ? 'selected' : '' }}>{{ $d->title }}</option>
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
                            <option value="{{ $val }}" {{ old('seniority_level') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Work Location</label>
                        <select name="work_location" class="form-select">
                            <option value="onsite" {{ old('work_location', 'onsite') === 'onsite' ? 'selected' : '' }}>Onsite</option>
                            <option value="remote" {{ old('work_location') === 'remote' ? 'selected' : '' }}>Remote</option>
                            <option value="hybrid" {{ old('work_location') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GitHub Username</label>
                        <input type="text" name="github_username" class="form-input" value="{{ old('github_username') }}" placeholder="github-username">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Skills</label>
                        <input type="text" name="skills_input" id="skills-input" class="form-input" placeholder="PHP, Laravel, React (comma separated)" value="{{ old('skills_input') }}">
                        <p class="form-hint">Separate skills with commas</p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.users') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Create User</button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-users.js') }}"></script>
@endpush
