@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/leaves.css') }}">

<div class="leave-page">

  <a href="{{ route('hr-onboarding.index') }}"
     style="font-size:12px;color:#6b7280;text-decoration:none;
            display:inline-flex;align-items:center;gap:4px;margin-bottom:12px">
    ← Back to Onboarding
  </a>

  <div class="leave-page-header">
    <div>
      <h1 class="leave-page-title">New Onboarding Checklist</h1>
      <p class="leave-page-sub">
        Select an employee and customize their onboarding tasks.
      </p>
    </div>
  </div>

  @if(session('error'))
    <div style="background:#fcebeb;border:0.5px solid #f7c1c1;color:#a32d2d;
                padding:10px 14px;border-radius:8px;font-size:13px;
                margin-bottom:1rem">
      {{ session('error') }}
    </div>
  @endif

  <form method="POST" action="{{ route('hr-onboarding.store') }}"
        style="max-width:760px">
    @csrf

    {{-- CARD 1: Employee + Due Date --}}
    <div class="leave-card" style="margin-bottom:1rem">
      <div class="leave-section-title" style="margin-bottom:1rem">
        Employee Details
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label class="leave-label">
            Employee <span style="color:#a32d2d">*</span>
          </label>
          <select name="user_id" class="leave-input" required>
            <option value="">Select employee...</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}"
                {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                {{ $emp->name }}
                @if($emp->employeeProfile?->designation)
                  — {{ $emp->employeeProfile->designation }}
                @endif
              </option>
            @endforeach
          </select>
          @error('user_id')
            <span style="font-size:11px;color:#a32d2d">{{ $message }}</span>
          @enderror
        </div>
        <div>
          <label class="leave-label">Due Date</label>
          <input type="date" name="due_date" class="leave-input"
                 value="{{ old('due_date') }}"
                 min="{{ now()->toDateString() }}">
        </div>
      </div>
    </div>

    {{-- CARD 2: Tasks --}}
    <div class="leave-card" style="margin-bottom:1rem">
      <div style="display:flex;justify-content:space-between;
                  align-items:center;margin-bottom:1rem">
        <div>
          <div class="leave-section-title">Onboarding Tasks</div>
          <div style="font-size:12px;color:#6b7280;margin-top:2px">
            Edit, remove or reorder tasks before creating
          </div>
        </div>
        <button type="button" onclick="addTask()" class="leave-btn"
                style="font-size:12px;padding:6px 14px">
          + Add Task
        </button>
      </div>

      <div id="tasks-list"
           style="display:flex;flex-direction:column;gap:8px">
      </div>
      <div id="tasks-inputs"></div>
    </div>

    {{-- BUTTONS --}}
    <div style="display:flex;gap:8px;align-items:center">
      <button type="submit" class="leave-btn leave-btn-primary"
              style="width:auto;padding:9px 20px">
        Create Checklist
      </button>
      <a href="{{ route('hr-onboarding.index') }}"
         style="font-size:13px;color:#6b7280;text-decoration:none;
                padding:9px 16px">
        Cancel
      </a>
    </div>

  </form>
</div>

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
      'display:flex;align-items:center;gap:10px;padding:10px 14px;' +
      'background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:8px';

    var num = document.createElement('span');
    num.textContent = i + 1;
    num.style.cssText =
      'width:22px;height:22px;border-radius:50%;background:#f3f4f6;' +
      'color:#6b7280;font-size:11px;font-weight:500;display:flex;' +
      'align-items:center;justify-content:center;flex-shrink:0';

    var inp = document.createElement('input');
    inp.type  = 'text';
    inp.value = task;
    inp.style.cssText =
      'flex:1;border:none;background:transparent;font-size:13px;' +
      'color:#18181b;outline:none;min-width:0';
    inp.addEventListener('input', function() {
      tasks[i] = this.value;
      syncHidden();
    });

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = '✕';
    btn.style.cssText =
      'border:0.5px solid #f7c1c1;background:transparent;color:#a32d2d;' +
      'border-radius:6px;width:26px;height:26px;cursor:pointer;' +
      'font-size:12px;flex-shrink:0;display:flex;align-items:center;' +
      'justify-content:center';
    btn.onclick = function() { tasks.splice(i, 1); renderTasks(); };

    row.appendChild(num);
    row.appendChild(inp);
    row.appendChild(btn);
    list.appendChild(row);

    var h = document.createElement('input');
    h.type  = 'hidden';
    h.name  = 'tasks[]';
    h.value = task;
    inputs.appendChild(h);
  });
}

function syncHidden() {
  var inputs = document.getElementById('tasks-inputs');
  inputs.innerHTML = '';
  tasks.forEach(function(t) {
    var h = document.createElement('input');
    h.type  = 'hidden';
    h.name  = 'tasks[]';
    h.value = t;
    inputs.appendChild(h);
  });
}

function addTask() {
  tasks.push('');
  renderTasks();
  var rows = document.getElementById('tasks-list').children;
  var last = rows[rows.length - 1].querySelector('input[type=text]');
  if (last) last.focus();
}

renderTasks();
</script>

@endsection
