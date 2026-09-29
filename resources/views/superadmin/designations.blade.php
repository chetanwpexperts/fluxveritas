@extends('layouts.app')
@section('title', 'Platform Designations')

@section('content')
<div class="page-wrapper">
<div class="sa-page-header">
    <div class="sa-page-header-left">
        <h1 class="sa-page-title">Platform Designations</h1>
        <p class="sa-page-sub">Manage built-in designation templates available to all organizations</p>
    </div>
</div>

<div class="sa-stats-row">
    <div class="sa-stat-card">
        <div class="sa-stat-value">{{ $totalCount }}</div>
        <div class="sa-stat-label">Platform Templates</div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-value">{{ $orgCount }}</div>
        <div class="sa-stat-label">Org-Custom Designations</div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-value">{{ $designations->count() }}</div>
        <div class="sa-stat-label">Categories</div>
    </div>
</div>

{{-- Add new platform designation --}}
<div class="sa-card mb-lg">
    <div class="sa-card-header">
        <h3 class="sa-card-title">Add Platform Designation</h3>
    </div>
    <form method="POST" action="{{ route('superadmin.designations.store') }}" class="sa-inline-form">
        @csrf
        <div class="sa-form-row">
            <input type="text" name="title" class="sa-input" placeholder="Designation title" required value="{{ old('title') }}">
            <select name="category" class="sa-select" required>
                <option value="">Category...</option>
                @foreach(['engineering' => 'Engineering', 'qa' => 'QA', 'design' => 'Design', 'product' => 'Product/Management', 'hr' => 'HR', 'marketing' => 'Marketing', 'finance' => 'Finance', 'operations' => 'Operations'] as $val => $label)
                <option value="{{ $val }}" {{ old('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="seniority_level" class="sa-select">
                <option value="">Any level</option>
                @foreach(['intern' => 'Intern', 'junior' => 'Junior', 'mid' => 'Mid Level', 'senior' => 'Senior', 'lead' => 'Lead', 'principal' => 'Principal', 'manager' => 'Manager', 'director' => 'Director', 'c_level' => 'C-Level'] as $val => $label)
                <option value="{{ $val }}" {{ old('seniority_level') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <label class="sa-check-label">
                <input type="hidden" name="requires_github" value="0">
                <input type="checkbox" name="requires_github" value="1" {{ old('requires_github') ? 'checked' : '' }}>
                GitHub tracking
            </label>
            <button type="submit" class="sa-btn sa-btn-primary">Add Designation</button>
        </div>
        @error('title')<span class="sa-form-error">{{ $message }}</span>@enderror
        @error('category')<span class="sa-form-error">{{ $message }}</span>@enderror
    </form>
</div>

{{-- Grouped by category --}}
@forelse($designations as $category => $items)
<div class="sa-card mb-md">
    <div class="sa-card-header">
        <h3 class="sa-card-title">
            {{ ucfirst($category) }}
            <span class="sa-badge sa-badge-gray">{{ $items->count() }}</span>
        </h3>
    </div>
    <table class="sa-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Seniority</th>
                <th>GitHub</th>
                <th>Orgs Using</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $designation)
            <tr>
                <td class="sa-td-bold">{{ $designation->title }}</td>
                <td><code>{{ $designation->slug }}</code></td>
                <td>{{ $designation->seniority_level ? ucfirst(str_replace('_', ' ', $designation->seniority_level)) : 'All levels' }}</td>
                <td>{{ $designation->requires_github ? 'Yes' : 'No' }}</td>
                <td>{{ $designation->users()->count() }}</td>
                <td>
                    <form method="POST" action="{{ route('superadmin.designations.delete', $designation->id) }}"
                          data-confirm="Delete '{{ $designation->title }}' platform designation?">
                        @csrf @method('DELETE')
                        <button type="submit" class="sa-btn sa-btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@empty
<div class="sa-empty">No platform designations found.</div>
@endforelse

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-users.js') }}"></script>
@endpush
