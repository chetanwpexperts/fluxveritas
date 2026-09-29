@extends('layouts.app')
@section('title', 'New Onboarding Checklist')
@section('content')
<div class="page-wrapper">

    {{-- HEADER --}}
    <div class="page-header">
        <div class="page-header-left">
            <a href="{{ route('onboarding.index') }}"
                class="stng-back-btn mb-sm">
                ← Back to Onboarding
            </a>
            <h1 class="page-title">
                New Onboarding Checklist
            </h1>
            <p class="page-subtitle">
                Select an employee and customize
                their onboarding tasks
            </p>
        </div>
    </div>

    @if(session('error'))
    <div class="fv-alert fv-alert-error mb-md">
        ❌ {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="fv-alert fv-alert-error mb-md">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form method="POST"
        action="{{ route('onboarding.store') }}"
        style="max-width:760px">
        @csrf

        {{-- EMPLOYEE DETAILS --}}
        <div class="fv-card p-lg mb-md">
            <div class="fv-section-title mb-md">
                👤 Employee Details
            </div>
            <div style="display:grid;
                grid-template-columns:1fr 1fr;
                gap:16px">

                <div class="form-group">
                    <label class="form-label">
                        Employee *
                    </label>
                    <select name="user_id"
                        class="form-control" required>
                        <option value="">
                            Select employee...
                        </option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ old('user_id') == $emp->id
                                ? 'selected' : '' }}>
                            {{ $emp->name }}
                            @if($emp->employeeProfile
                                ?->designation)
                            — {{ $emp->employeeProfile
                                ->designation }}
                            @endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Due Date
                    </label>
                    <input type="date"
                        name="due_date"
                        class="form-control"
                        value="{{ old('due_date') }}"
                        min="{{ now()->toDateString() }}">
                </div>

            </div>
        </div>

        {{-- ONBOARDING TASKS --}}
        <div class="fv-card mb-md">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">
                        ✅ Onboarding Tasks
                    </div>
                    <div class="fv-section-sub">
                        Edit, remove or add tasks
                        before creating
                    </div>
                </div>
                <button type="button"
                    onclick="addTask()"
                    class="btn-secondary btn-sm">
                    + Add Task
                </button>
            </div>

            <div style="padding:0 20px 20px">
                <div id="tasks-list"
                    style="display:flex;
                    flex-direction:column;gap:8px">
                </div>
                <div id="tasks-inputs"></div>
            </div>
        </div>

        {{-- SUBMIT --}}
        <div style="display:flex;gap:8px">
            <button type="submit"
                class="btn-primary">
                Create Checklist
            </button>
            <a href="{{ route('onboarding.index') }}"
                class="btn-secondary">
                Cancel
            </a>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
var tasks = [
    "Send welcome email to new employee",
    "Create system accounts (email, Slack, GitHub)",
    "Assign to department and team in OutraqHQ",
    "Share company handbook",
    "Schedule first day orientation",
    "Set up workstation / access cards",
    "Introduce to reporting manager",
    "Complete profile in OutraqHQ"
];

function renderTasks() {
    var list   = document.getElementById('tasks-list');
    var inputs = document.getElementById('tasks-inputs');
    list.innerHTML   = '';
    inputs.innerHTML = '';

    tasks.forEach(function(task, i) {
        var row = document.createElement('div');
        row.style.cssText =
            'display:flex;align-items:center;gap:10px;'
            + 'padding:10px 12px;background:#fafafa;'
            + 'border:1px solid #f4f4f5;border-radius:10px';

        // Number badge
        var num = document.createElement('span');
        num.textContent = i + 1;
        num.style.cssText =
            'width:24px;height:24px;border-radius:50%;'
            + 'background:#e4e4e7;color:#71717a;'
            + 'font-size:11px;font-weight:700;'
            + 'display:flex;align-items:center;'
            + 'justify-content:center;flex-shrink:0';

        // Text input
        var inp = document.createElement('input');
        inp.type  = 'text';
        inp.value = task;
        inp.style.cssText =
            'flex:1;border:none;background:transparent;'
            + 'font-size:13px;color:#18181b;'
            + 'outline:none;min-width:0;'
            + 'font-family:inherit';
        inp.placeholder = 'Task description...';
        inp.addEventListener('input', function() {
            tasks[i] = this.value;
            syncHidden();
        });

        // Remove button
        var btn = document.createElement('button');
        btn.type      = 'button';
        btn.innerHTML = '✕';
        btn.style.cssText =
            'border:1px solid #fecaca;background:#fef2f2;'
            + 'color:#991b1b;border-radius:6px;'
            + 'width:28px;height:28px;cursor:pointer;'
            + 'font-size:12px;flex-shrink:0;'
            + 'display:flex;align-items:center;'
            + 'justify-content:center;'
            + 'transition:all 0.15s';
        btn.onmouseover = function() {
            this.style.background = '#ef4444';
            this.style.color = '#fff';
        };
        btn.onmouseout = function() {
            this.style.background = '#fef2f2';
            this.style.color = '#991b1b';
        };
        btn.onclick = function() {
            tasks.splice(i, 1);
            renderTasks();
        };

        row.appendChild(num);
        row.appendChild(inp);
        row.appendChild(btn);
        list.appendChild(row);

        // Hidden input for form submission
        var h  = document.createElement('input');
        h.type = 'hidden';
        h.name = 'tasks[]';
        h.value = task;
        inputs.appendChild(h);
    });
}

function syncHidden() {
    var inputs = document.getElementById('tasks-inputs');
    inputs.innerHTML = '';
    tasks.forEach(function(t) {
        var h  = document.createElement('input');
        h.type = 'hidden';
        h.name = 'tasks[]';
        h.value = t;
        inputs.appendChild(h);
    });
}

function addTask() {
    tasks.push('');
    renderTasks();
    var rows = document.getElementById(
        'tasks-list'
    ).children;
    var last = rows[rows.length - 1]
        .querySelector('input[type=text]');
    if (last) last.focus();
}

renderTasks();
</script>
@endpush
