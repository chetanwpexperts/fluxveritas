@extends('layouts.app')

@section('content')
<div style="max-width:700px;margin:0 auto;padding:32px 24px;">

<div class="mb-lg">
    <a href="{{ route('departments.index') }}" style="font-size:0.8rem;color:#94a3b8;text-decoration:none;font-weight:600;">← Departments</a>
    <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;letter-spacing:-0.025em;margin:8px 0 4px;">Add Department</h1>
    <p style="color:#94a3b8;font-size:0.9rem;margin:0;">Create a new department to organize your team</p>
</div>

@if($errors->any())
<div class="fv-alert fv-alert-warning" style="margin-bottom:20px;">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="fv-card" style="padding:28px;">
    <form method="POST" action="{{ route('departments.store') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Department Name <span style="color:#ef4444;">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Engineering, Sales, HR"
                   style="width:100%;padding:10px 14px;border:1px solid {{ $errors->has('name') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.9rem;font-family:inherit;color:#0f172a;box-sizing:border-box;"
                   required>
            @error('name')
            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Type <span style="color:#ef4444;">*</span></label>
                <select name="type" style="width:100%;padding:10px 14px;border:1px solid {{ $errors->has('type') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.9rem;font-family:inherit;background:white;color:#0f172a;" required>
                    <option value="">Select type…</option>
                    @foreach(['tech'=>'Technology','sales'=>'Sales','hr'=>'Human Resources','finance'=>'Finance','operations'=>'Operations','design'=>'Design','marketing'=>'Marketing','other'=>'Other'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Work Mode <span style="color:#ef4444;">*</span></label>
                <select name="work_mode" style="width:100%;padding:10px 14px;border:1px solid {{ $errors->has('work_mode') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.9rem;font-family:inherit;background:white;color:#0f172a;" required>
                    <option value="manual" {{ old('work_mode','manual') === 'manual' ? 'selected' : '' }}>Manual — Daily work logs</option>
                    <option value="github" {{ old('work_mode') === 'github' ? 'selected' : '' }}>GitHub — Commit & PR tracking</option>
                    <option value="hybrid" {{ old('work_mode') === 'hybrid' ? 'selected' : '' }}>Hybrid — Both</option>
                </select>
                @error('work_mode')
                <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Color</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" name="color" value="{{ old('color','#18181b') }}"
                           style="width:44px;height:38px;padding:2px;border:1px solid #e4e4e7;border-radius:8px;cursor:pointer;">
                    <span style="font-size:0.78rem;color:#94a3b8;">Used for visual identification</span>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Department Head</label>
                <select name="head_user_id" style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;background:white;color:#0f172a;">
                    <option value="">No head assigned</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}" {{ old('head_user_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-lg">
            <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Description</label>
            <textarea name="description" rows="3" placeholder="Brief description of this department's role…"
                      style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;resize:vertical;color:#0f172a;box-sizing:border-box;">{{ old('description') }}</textarea>
            @error('description')
            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="padding:12px 16px;background:#f8fafc;border-radius:8px;margin-bottom:24px;font-size:0.8rem;color:#64748b;">
            Default metrics will be created automatically based on the department type selected.
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a href="{{ route('departments.index') }}" class="fv-btn fv-btn-secondary">Cancel</a>
            <button type="submit" class="fv-btn fv-btn-primary">Create Department</button>
        </div>
    </form>
</div>

</div>
@endsection
