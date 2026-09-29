@extends('layouts.app')

@section('content')
<div style="max-width:700px;margin:0 auto;padding:32px 24px;">

<div class="mb-lg">
    <a href="{{ route('departments.show', $dept->id) }}" style="font-size:0.8rem;color:#94a3b8;text-decoration:none;font-weight:600;">← {{ $dept->name }}</a>
    <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;letter-spacing:-0.025em;margin:8px 0 4px;">Edit Department</h1>
</div>

@if($errors->any())
<div class="fv-alert fv-alert-warning" style="margin-bottom:20px;">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="fv-card" style="padding:28px;">
    <form method="POST" action="{{ route('departments.update', $dept->id) }}">
        @csrf @method('PATCH')

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Department Name *</label>
            <input type="text" name="name" value="{{ old('name', $dept->name) }}"
                   style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;color:#0f172a;box-sizing:border-box;"
                   required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Type *</label>
                <select name="type" style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;background:white;color:#0f172a;" required>
                    @foreach(['tech'=>'Technology','sales'=>'Sales','hr'=>'Human Resources','finance'=>'Finance','operations'=>'Operations','design'=>'Design','marketing'=>'Marketing','other'=>'Other'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('type',$dept->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Work Mode *</label>
                <select name="work_mode" style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;background:white;color:#0f172a;" required>
                    <option value="manual" {{ old('work_mode',$dept->work_mode) === 'manual' ? 'selected' : '' }}>Manual</option>
                    <option value="github" {{ old('work_mode',$dept->work_mode) === 'github' ? 'selected' : '' }}>GitHub</option>
                    <option value="hybrid" {{ old('work_mode',$dept->work_mode) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Color</label>
                <input type="color" name="color" value="{{ old('color', $dept->color) }}"
                       style="width:44px;height:38px;padding:2px;border:1px solid #e4e4e7;border-radius:8px;cursor:pointer;">
            </div>
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Department Head</label>
                <select name="head_user_id" style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;background:white;color:#0f172a;">
                    <option value="">No head assigned</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}" {{ old('head_user_id',$dept->head_user_id) == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-lg">
            <label style="display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;">Description</label>
            <textarea name="description" rows="3"
                      style="width:100%;padding:10px 14px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.9rem;font-family:inherit;resize:vertical;color:#0f172a;box-sizing:border-box;">{{ old('description', $dept->description) }}</textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a href="{{ route('departments.show', $dept->id) }}" class="fv-btn fv-btn-secondary">Cancel</a>
            <button type="submit" class="fv-btn fv-btn-primary">Save Changes</button>
        </div>
    </form>
</div>

</div>
@endsection
