@extends('layouts.app')
@section('title', 'New Sprint')
@section('content')

<div style="max-width:700px;margin:0 auto;padding:28px 24px;font-family:'Plus Jakarta Sans',sans-serif;">

    {{-- BREADCRUMB --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
        <a href="{{ route('sprints.index') }}" style="font-size:0.82rem;color:#71717a;text-decoration:none;font-weight:600;">← Sprints</a>
        <span style="color:#a1a1aa;font-size:0.82rem;">›</span>
        <span style="font-size:0.82rem;font-weight:700;color:#09090b;">New Sprint</span>
    </div>

    {{-- ERRORS --}}
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 14px;margin-bottom:20px;">
        @foreach($errors->all() as $e)
        <div style="font-size:0.78rem;color:#dc2626;">• {{ $e }}</div>
        @endforeach
    </div>
    @endif

    {{-- FORM CARD --}}
    <form method="POST" action="{{ route('sprints.store') }}">
        @csrf
        <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:24px 26px;">

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- 1. SPRINT NAME --}}
                <div>
                    <label for="name" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">
                        Sprint Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        required
                        value="{{ old('name') }}"
                        placeholder="e.g. Sprint 1, Q2 Sprint 3"
                        style="padding:8px 10px;border:1px solid {{ $errors->has('name') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;width:100%;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;"
                    >
                    @error('name')
                    <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 2. PROJECT --}}
                <div>
                    <label for="project_id" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">
                        Project <span style="color:#ef4444;">*</span>
                    </label>
                    <select
                        name="project_id"
                        id="project_id"
                        required
                        style="padding:8px 10px;border:1px solid {{ $errors->has('project_id') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;width:100%;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;"
                    >
                        <option value="">Select a project…</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')
                    <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 3. GOAL --}}
                <div>
                    <label for="goal" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">
                        Goal <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span>
                    </label>
                    <textarea
                        name="goal"
                        id="goal"
                        rows="3"
                        placeholder="What should this sprint achieve? What is the main deliverable?"
                        style="padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;width:100%;box-sizing:border-box;resize:vertical;font-family:'Plus Jakarta Sans',sans-serif;line-height:1.5;"
                    >{{ old('goal') }}</textarea>
                </div>

                {{-- 4. DATES --}}
                <div>
                    <label style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">
                        Dates
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label for="start_date" style="display:block;font-size:0.72rem;color:#a1a1aa;margin-bottom:4px;">Start Date</label>
                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                value="{{ old('start_date') }}"
                                style="padding:8px 10px;border:1px solid {{ $errors->has('start_date') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;width:100%;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;"
                            >
                            @error('start_date')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" style="display:block;font-size:0.72rem;color:#a1a1aa;margin-bottom:4px;">End Date</label>
                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                value="{{ old('end_date') }}"
                                style="padding:8px 10px;border:1px solid {{ $errors->has('end_date') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;width:100%;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;"
                            >
                            @error('end_date')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                            <div style="font-size:0.7rem;color:#a1a1aa;margin-top:4px;">Suggested: 2 weeks from start</div>
                        </div>
                    </div>
                </div>

                {{-- 5. DURATION CALCULATOR --}}
                <div id="duration-display" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:0.82rem;color:#16a34a;font-weight:600;">
                    📅 Duration: <span id="duration-text"></span>
                </div>

            </div>{{-- end fields --}}

            {{-- ACTIONS ROW --}}
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;padding-top:20px;border-top:1px solid #f4f4f5;">
                <a href="{{ route('sprints.index') }}" style="font-size:0.82rem;color:#71717a;text-decoration:none;font-weight:600;padding:8px 0;">Cancel</a>
                <button
                    type="submit"
                    style="padding:8px 20px;background:#09090b;color:white;border:none;border-radius:8px;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;"
                >Create Sprint</button>
            </div>

        </div>
    </form>

</div>

@push('scripts')
<script src="{{ asset('js/sprints-create.js') }}"></script>
@endpush

@endsection
