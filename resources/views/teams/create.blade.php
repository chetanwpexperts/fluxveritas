@extends('layouts.app')
@section('title', 'Create Team')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <a href="{{ route('teams.index') }}" class="stng-back-btn" style="margin-bottom:8px;display:inline-block">← Back to Teams</a>
            <h1 class="page-title">Create Team</h1>
            <p class="page-subtitle">Min 3 members · Max 10 members per team</p>
        </div>
    </div>

    <div class="fv-card" style="max-width:600px;padding:28px">

        @if($errors->any())
        <div class="fv-alert fv-alert-error mb-md">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('teams.store') }}">
            @csrf

            <div class="form-group mb-md">
                <label class="form-label">Team Name *</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control" placeholder="e.g. Frontend Team" required>
            </div>

            <div class="form-group mb-md">
                <label class="form-label">Department *</label>
                <select name="department_id" class="form-control" required>
                    <option value="">Select department...</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-md">
                <label class="form-label">Team Lead (optional)</label>
                <select name="team_lead_id" class="form-control">
                    <option value="">Assign later...</option>
                    @foreach($potentialLeads as $lead)
                    <option value="{{ $lead->id }}" {{ old('team_lead_id') == $lead->id ? 'selected' : '' }}>
                        {{ $lead->name }} ({{ $lead->getRoleNames()->first() ?? 'employee' }})
                    </option>
                    @endforeach
                </select>
                <div class="form-hint">The selected person will be assigned to this team as Team Lead.</div>
            </div>

            <div class="form-group mb-lg">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" class="form-control" rows="3"
                    placeholder="What does this team do?">{{ old('description') }}</textarea>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn-primary">Create Team</button>
                <a href="{{ route('teams.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

</div>
@endsection
