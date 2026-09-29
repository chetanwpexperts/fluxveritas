@extends('layouts.app')
@section('title', 'New Task')
@section('content')
<div style="max-width:800px;margin:0 auto;padding:28px 24px;">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('tasks.index') }}" style="font-size:0.8rem;color:#71717a;text-decoration:none;font-weight:600;">← Tasks</a>
        <span style="color:#e4e4e7;">›</span>
        <h1 style="font-size:1.2rem;font-weight:800;color:#09090b;margin:0;">New Task</h1>
    </div>

    <form method="POST" action="{{ route('tasks.store') }}" style="display:flex;flex-direction:column;gap:16px;">
        @csrf

        @if($parentTask)
        <input type="hidden" name="parent_task_id" value="{{ $parentTask->id }}">
        <div style="padding:10px 14px;background:#f4f4f5;border-radius:8px;font-size:0.8rem;color:#71717a;">
            Sub-task of: <strong style="color:#09090b;">{{ $parentTask->ticket_number }} — {{ $parentTask->title }}</strong>
        </div>
        @endif

        <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:20px 22px;display:flex;flex-direction:column;gap:16px;">

            {{-- Type --}}
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Type</label>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach(['task'=>'📋 Task','bug'=>'🐛 Bug','feature'=>'✨ Feature','improvement'=>'📈 Improvement','story'=>'📖 Story','epic'=>'⚡ Epic','question'=>'❓ Question','incident'=>'🚨 Incident'] as $val=>$lbl)
                    <label class="pointer">
                        <input type="radio" name="type" value="{{ $val }}" {{ old('type','task')===$val?'checked':'' }} style="display:none;" class="type-radio">
                        <span class="type-btn" style="display:inline-block;padding:5px 12px;border:1px solid #e4e4e7;border-radius:7px;font-size:0.75rem;font-weight:600;background:{{ old('type','task')===$val?'#f4f4f5':'white' }};">{{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Title --}}
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Title <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="What needs to be done?"
                    style="width:100%;padding:10px 12px;border:1px solid {{ $errors->has('title') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.92rem;font-weight:600;color:#09090b;font-family:'Plus Jakarta Sans',sans-serif;outline:none;box-sizing:border-box;">
                @error('title')
                <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Description</label>
                <textarea name="description" rows="4" placeholder="Add more details..."
                    style="width:100%;padding:10px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.82rem;color:#3f3f46;font-family:'Plus Jakarta Sans',sans-serif;resize:vertical;outline:none;box-sizing:border-box;">{{ old('description') }}</textarea>
            </div>

            {{-- Row: Project + Assignee --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Project <span style="color:#ef4444;">*</span></label>
                    <select name="project_id" required style="width:100%;padding:8px 10px;border:1px solid {{ $errors->has('project_id') ? '#dc2626' : '#e4e4e7' }};border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">Select project...</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ (old('project_id',$preProject)==$p->id)?'selected':'' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')
                    <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Assignee</label>
                    <select name="assigned_to" style="width:100%;padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.82rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">Unassigned</option>
                        @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ old('assigned_to')==$m->id?'selected':'' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                    <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Priority --}}
            <div class="mb-xs">
                <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:10px;">Priority</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    @foreach([
                        ['critical', '🔴', 'Critical', '#fef2f2', '#dc2626'],
                        ['high',     '🟠', 'High',     '#fff7ed', '#ea580c'],
                        ['medium',   '🟡', 'Medium',   '#fefce8', '#ca8a04'],
                        ['low',      '🟢', 'Low',      '#f0fdf4', '#16a34a'],
                    ] as [$val, $icon, $label, $bg, $color])
                    <label class="pointer">
                        <input type="radio" name="priority" value="{{ $val }}"
                            {{ old('priority', 'medium') === $val ? 'checked' : '' }}
                            style="display:none;" class="priority-radio">
                        <div class="priority-option" data-value="{{ $val }}"
                            style="padding:8px 16px;border:2px solid #e4e4e7;border-radius:8px;font-size:0.82rem;font-weight:600;cursor:pointer;background:white;color:#71717a;transition:all 0.15s ease;display:flex;align-items:center;gap:6px;">
                            {{ $icon }} {{ $label }}
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Row: Label + Sprint + Due --}}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Label</label>
                    <select name="label" style="width:100%;padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">None</option>
                        @foreach(['frontend','backend','database','devops','design','testing','docs','meeting','research','other'] as $l)
                        <option value="{{ $l }}" {{ old('label')===$l?'selected':'' }}>{{ ucfirst($l) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Sprint</label>
                    <select name="sprint_id" style="width:100%;padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">No Sprint / Backlog</option>
                        @foreach($sprints as $s)
                        <option value="{{ $s->id }}" {{ old('sprint_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->project->name }} — {{ $s->name }} ({{ ucfirst($s->status) }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                        style="width:100%;padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;box-sizing:border-box;">
                    @error('due_date')
                    <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Row: Estimated hours + Department --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Estimated Hours</label>
                    <input type="number" name="estimated_hours" step="0.5" min="0" value="{{ old('estimated_hours') }}" placeholder="e.g. 4"
                        style="width:100%;padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.78rem;color:#3f3f46;box-sizing:border-box;outline:none;">
                    @error('estimated_hours')
                    <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#71717a;text-transform:uppercase;display:block;margin-bottom:6px;">Department</label>
                    <select name="department_id" style="width:100%;padding:8px 10px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">None</option>
                        @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ old('department_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <a href="{{ route('tasks.index') }}" style="font-size:0.82rem;color:#71717a;text-decoration:none;font-weight:600;">Cancel</a>
            <button type="submit" style="padding:10px 24px;background:#09090b;color:white;border:none;border-radius:8px;font-size:0.85rem;font-weight:700;cursor:pointer;">Create Task</button>
        </div>

        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 14px;">
            @foreach($errors->all() as $e)
            <div style="font-size:0.78rem;color:#dc2626;">• {{ $e }}</div>
            @endforeach
        </div>
        @endif
    </form>
</div>

@push('scripts')
<script src="{{ asset('js/tasks-create.js') }}"></script>
@endpush
@endsection
