@extends('layouts.app')
@section('title', 'Edit Designation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Edit Designation</h1>
            <p class="page-subtitle">{{ $designation->title }}</p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('admin.designations') }}" class="btn-secondary">← Back</a>
        </div>
    </div>

    @if($designation->is_template)
    <div class="fv-alert fv-alert-warning mb-md">⚠️ This is a platform template. Changes here will only affect your organization's view.</div>
    @endif

    <div class="admin-form-card">
        <form method="POST" action="{{ route('admin.designations.update', $designation->id) }}">
            @csrf
            @method('PATCH')
            <div class="form-section">
                <h3 class="form-section-title">Designation Details</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-input" required value="{{ old('title', $designation->title) }}">
                        @error('title')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-select" required>
                            @foreach(['engineering' => 'Engineering', 'qa' => 'QA', 'design' => 'Design', 'product' => 'Product/Management', 'hr' => 'HR', 'marketing' => 'Marketing', 'finance' => 'Finance', 'operations' => 'Operations'] as $val => $label)
                            <option value="{{ $val }}" {{ old('category', $designation->category) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seniority Level</label>
                        <select name="seniority_level" class="form-select">
                            <option value="">All levels</option>
                            @foreach(['intern' => 'Intern', 'junior' => 'Junior', 'mid' => 'Mid Level', 'senior' => 'Senior', 'lead' => 'Lead', 'principal' => 'Principal', 'manager' => 'Manager', 'director' => 'Director', 'c_level' => 'C-Level'] as $val => $label)
                            <option value="{{ $val }}" {{ old('seniority_level', $designation->seniority_level) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GitHub Tracking</label>
                        <div class="form-check-row">
                            <input type="hidden" name="requires_github" value="0">
                            <input type="checkbox" name="requires_github" value="1" id="requires_github" {{ old('requires_github', $designation->requires_github) ? 'checked' : '' }} class="form-checkbox">
                            <label for="requires_github" class="form-check-label">Requires GitHub metrics for scoring</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Active</label>
                        <div class="form-check-row">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $designation->is_active) ? 'checked' : '' }} class="form-checkbox">
                            <label for="is_active" class="form-check-label">Designation is active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.designations') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

</div>
@endsection
