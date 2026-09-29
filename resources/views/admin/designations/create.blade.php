@extends('layouts.app')
@section('title', 'Add Designation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Add Designation</h1>
            <p class="page-subtitle">Create a custom designation for your organization</p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('admin.designations') }}" class="btn-secondary">← Back</a>
        </div>
    </div>

    <div class="admin-form-card">
        <form method="POST" action="{{ route('admin.designations.store') }}">
            @csrf
            <div class="form-section">
                <h3 class="form-section-title">Designation Details</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-input" required value="{{ old('title') }}" placeholder="e.g. Senior Data Engineer">
                        @error('title')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category...</option>
                            @foreach(['engineering' => 'Engineering', 'qa' => 'QA', 'design' => 'Design', 'product' => 'Product/Management', 'hr' => 'HR', 'marketing' => 'Marketing', 'finance' => 'Finance', 'operations' => 'Operations'] as $val => $label)
                            <option value="{{ $val }}" {{ old('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seniority Level</label>
                        <select name="seniority_level" class="form-select">
                            <option value="">All levels</option>
                            @foreach(['intern' => 'Intern', 'junior' => 'Junior', 'mid' => 'Mid Level', 'senior' => 'Senior', 'lead' => 'Lead', 'principal' => 'Principal', 'manager' => 'Manager', 'director' => 'Director', 'c_level' => 'C-Level'] as $val => $label)
                            <option value="{{ $val }}" {{ old('seniority_level') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GitHub Tracking</label>
                        <div class="form-check-row">
                            <input type="hidden" name="requires_github" value="0">
                            <input type="checkbox" name="requires_github" value="1" id="requires_github" {{ old('requires_github') ? 'checked' : '' }} class="form-checkbox">
                            <label for="requires_github" class="form-check-label">Requires GitHub metrics for scoring</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.designations') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Create Designation</button>
            </div>
        </form>
    </div>

</div>
@endsection
