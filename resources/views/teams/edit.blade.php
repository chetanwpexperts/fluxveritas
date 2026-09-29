@extends('layouts.app')
@section('title', 'Edit Team')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <a href="{{ route('teams.show', $team->id) }}" class="stng-back-btn" style="margin-bottom:8px;display:inline-block">← Back to Team</a>
            <h1 class="page-title">Edit: {{ $team->name }}</h1>
        </div>
        <div class="page-header-right">
            <form method="POST" action="{{ route('teams.destroy', $team->id) }}"
                onsubmit="return confirm('Delete this team? Members will be unassigned.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Delete Team</button>
            </form>
        </div>
    </div>

    <div class="fv-card" style="max-width:600px;padding:28px">

        @if($errors->any())
        <div class="fv-alert fv-alert-error mb-md">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('teams.update', $team->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group mb-md">
                <label class="form-label">Team Name *</label>
                <input type="text" name="name" value="{{ old('name', $team->name) }}"
                    class="form-control" required>
            </div>

            <div class="form-group mb-md">
                <label class="form-label">Department *</label>
                <select name="department_id" class="form-control" required>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $team->department_id == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-md">
                <label class="form-label">Team Lead</label>
                <select name="team_lead_id" class="form-control">
                    <option value="">None</option>
                    @foreach($potentialLeads as $lead)
                    <option value="{{ $lead->id }}" {{ $team->team_lead_id == $lead->id ? 'selected' : '' }}>
                        {{ $lead->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-lg">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $team->description) }}</textarea>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('teams.show', $team->id) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

</div>
@endsection
