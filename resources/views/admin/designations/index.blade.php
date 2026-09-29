@extends('layouts.app')
@section('title', 'Designations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Designations</h1>
            <p class="page-subtitle">Manage job designations for your organization</p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('admin.designations.create') }}" class="btn-primary">+ Add Designation</a>
        </div>
    </div>

    @include('settings.partials.tabs')

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="fv-alert fv-alert-error mb-md">{{ session('error') }}</div>
    @endif

    @forelse($designations as $category => $items)
    <div class="designation-category-section">
        <h3 class="designation-category-title">
            {{ ucfirst($category) }}
            <span class="designation-count">{{ $items->count() }}</span>
        </h3>
        <div class="designation-grid">
            @foreach($items as $designation)
            <div class="designation-card {{ !$designation->is_active ? 'designation-inactive' : '' }}">
                <div class="designation-card-top">
                    <span class="designation-title">{{ $designation->title }}</span>
                    @if($designation->is_template)
                    <span class="designation-badge-template">Platform Template</span>
                    @endif
                </div>
                <div class="designation-card-meta">
                    <span class="designation-meta-item">{{ ucfirst($designation->seniority_level ?? 'All levels') }}</span>
                    <span class="designation-meta-item">{{ $designation->requires_github ? '⚡ GitHub tracked' : '📝 Manual tracking' }}</span>
                    <span class="designation-meta-item">{{ $designation->users()->count() }} users</span>
                </div>
                <div class="designation-card-actions">
                    @if(!$designation->is_template)
                    <a href="{{ route('admin.designations.edit', $designation->id) }}" class="sa-btn sa-btn-view">Edit</a>
                    <form method="POST" action="{{ route('admin.designations.delete', $designation->id) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sa-btn sa-btn-delete"
                            data-confirm="Delete {{ $designation->title }}?">Delete</button>
                    </form>
                    @else
                    <span class="designation-locked">🔒 Platform template</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="fv-empty">
        <div class="fv-empty-title">No designations found</div>
    </div>
    @endforelse

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-users.js') }}"></script>
@endpush
