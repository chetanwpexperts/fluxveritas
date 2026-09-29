@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/leaves.css') }}">

<div class="leave-page">

  {{-- HEADER --}}
  <div class="leave-page-header">
    <a href="{{ route('leaves.index') }}"
       style="font-size:12px;color:#6b7280;text-decoration:none;
              display:inline-flex;align-items:center;gap:4px;margin-bottom:12px">
      ← Back to Leaves
    </a>
    <h1 class="leave-page-title">Leave Settings</h1>
    <p class="leave-page-sub">
      Manage leave types and allocations for your organization
    </p>
  </div>

  {{-- FLASH MESSAGES --}}
  @if(session('success'))
    <div style="background:#eaf3de;border:0.5px solid #c0dd97;color:#3b6d11;
                padding:10px 14px;border-radius:8px;font-size:13px;
                margin-bottom:1rem;">
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div style="background:#fcebeb;border:0.5px solid #f7c1c1;color:#a32d2d;
                padding:10px 14px;border-radius:8px;font-size:13px;
                margin-bottom:1rem;">
      {{ session('error') }}
    </div>
  @endif

  {{-- TWO COLUMN GRID --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;
              align-items:start">

    {{-- LEFT COLUMN --}}
    <div>

      {{-- ADD LEAVE TYPE CARD --}}
      <div class="leave-card" style="margin-bottom:1rem">
        <div style="margin-bottom:1.25rem">
          <div style="font-size:14px;font-weight:500;color:#18181b;margin-bottom:3px">
            Add Leave Type
          </div>
          <div style="font-size:12px;color:#6b7280">
            Create a new leave type for your organization
          </div>
        </div>

        <form method="POST" action="{{ route('leaves.types.store') }}">
          @csrf

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;
                      margin-bottom:14px">
            <div>
              <label class="leave-label">Name</label>
              <input type="text" name="name" class="leave-input"
                     placeholder="e.g. Annual Leave"
                     value="{{ old('name') }}" required>
              @error('name')
                <span style="font-size:11px;color:#a32d2d">{{ $message }}</span>
              @enderror
            </div>
            <div>
              <label class="leave-label">Code</label>
              <input type="text" name="code" class="leave-input"
                     placeholder="AL" maxlength="5"
                     value="{{ old('code') }}" required
                     style="text-transform:uppercase">
              @error('code')
                <span style="font-size:11px;color:#a32d2d">{{ $message }}</span>
              @enderror
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;
                      margin-bottom:14px">
            <div>
              <label class="leave-label">Days / year</label>
              <input type="number" name="days_per_year" class="leave-input"
                     value="{{ old('days_per_year', 0) }}" min="0" required>
              @error('days_per_year')
                <span style="font-size:11px;color:#a32d2d">{{ $message }}</span>
              @enderror
            </div>
            <div>
              <label class="leave-label">Max carry forward</label>
              <input type="number" name="max_carry_forward" class="leave-input"
                     value="{{ old('max_carry_forward', 0) }}" min="0">
            </div>
          </div>

          <div style="margin-bottom:14px">
            <label class="leave-label">Description (optional)</label>
            <input type="text" name="description" class="leave-input"
                   placeholder="Brief description..."
                   value="{{ old('description') }}">
          </div>

          {{-- DIVIDER --}}
          <div style="height:0.5px;background:#f3f4f6;margin:1rem 0"></div>
          <div style="font-size:11px;font-weight:500;color:#6b7280;
                      text-transform:uppercase;letter-spacing:0.04em;
                      margin-bottom:10px">Options</div>

          {{-- TOGGLE OPTIONS --}}
          @php
            $toggles = [
              ['name' => 'is_paid',          'label' => 'Paid Leave',
               'sub'  => 'Employee receives salary during this leave',
               'default' => true],
              ['name' => 'requires_approval', 'label' => 'Requires Approval',
               'sub'  => 'Manager must approve before leave is granted',
               'default' => true],
              ['name' => 'allow_carry_forward','label' => 'Allow Carry Forward',
               'sub'  => 'Unused days roll over to the next year',
               'default' => false],
            ];
          @endphp

          <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
            @foreach($toggles as $toggle)
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:10px 12px;background:#f9fafb;border-radius:8px;
                        border:0.5px solid #f3f4f6">
              <div>
                <div style="font-size:13px;color:#18181b">{{ $toggle['label'] }}</div>
                <div style="font-size:11px;color:#9ca3af">{{ $toggle['sub'] }}</div>
              </div>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="hidden" name="{{ $toggle['name'] }}" value="0">
                <input type="checkbox" name="{{ $toggle['name'] }}" value="1"
                       {{ old($toggle['name'], $toggle['default']) ? 'checked' : '' }}
                       style="width:16px;height:16px;cursor:pointer;
                              accent-color:#18181b">
              </label>
            </div>
            @endforeach
          </div>

          <button type="submit" class="leave-btn leave-btn-primary"
                  style="width:100%;justify-content:center">
            Add Leave Type
          </button>
        </form>
      </div>

      {{-- BULK ALLOCATE CARD --}}
      <div class="leave-card">
        <div style="margin-bottom:1.25rem">
          <div style="font-size:14px;font-weight:500;color:#18181b;margin-bottom:3px">
            Bulk Allocate Leave
          </div>
          <div style="font-size:12px;color:#6b7280">
            Assign a leave type to all active employees for a given year.
            Creates balance records for employees who don't have one yet.
          </div>
        </div>

        <form method="POST" action="{{ route('leaves.allocate-all') }}">
          @csrf
          <div style="display:grid;grid-template-columns:1fr auto auto;
                      gap:10px;align-items:end">
            <div>
              <label class="leave-label">Leave type</label>
              <select name="leave_type_id" class="leave-input" required>
                <option value="">Select type...</option>
                @foreach($leaveTypes as $type)
                  <option value="{{ $type->id }}">
                    {{ $type->name }} ({{ $type->code }})
                  </option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="leave-label">Year</label>
              <input type="number" name="year" class="leave-input"
                     value="{{ now()->year }}"
                     style="width:90px" min="2020" max="2099">
            </div>
            <button type="submit" class="leave-btn leave-btn-primary"
                    style="white-space:nowrap;width:auto"
                    onclick="return confirm('Allocate to all active employees?')">
              Allocate to All
            </button>
          </div>
        </form>
      </div>

    </div>{{-- end left --}}

    {{-- RIGHT COLUMN — existing leave types --}}
    <div>
      <div class="leave-card">
        <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:4px">
          <div style="font-size:14px;font-weight:500;color:#18181b">
            Leave Types
          </div>
          <span style="font-size:11px;color:#9ca3af">
            {{ $leaveTypes->count() }} types configured
          </span>
        </div>
        <div style="font-size:12px;color:#6b7280;margin-bottom:1.25rem">
          Click Edit to update any leave type
        </div>

        @if($leaveTypes->isEmpty())
          <div class="leave-empty">
            <div class="leave-empty-icon">📋</div>
            <div class="leave-empty-text">
              No leave types yet. Add your first one.
            </div>
          </div>
        @else
          <div style="display:flex;flex-direction:column;gap:8px">
            @foreach($leaveTypes as $type)
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:12px 14px;background:var(--color-background-primary);
                        border:0.5px solid #e5e7eb;border-radius:10px">
              <div style="display:flex;align-items:center;gap:10px">
                {{-- CODE BADGE --}}
                <div style="font-size:11px;font-weight:600;padding:4px 8px;
                            border-radius:6px;background:#f3f4f6;color:#6b7280;
                            min-width:34px;text-align:center;letter-spacing:0.02em">
                  {{ $type->code }}
                </div>
                <div>
                  <div style="font-size:13px;font-weight:500;color:#18181b">
                    {{ $type->name }}
                  </div>
                  <div style="font-size:11px;color:#9ca3af;margin-top:1px">
                    {{ $type->days_per_year }} days/year
                    @if($type->max_carry_forward > 0)
                      · Carry fwd: {{ $type->max_carry_forward }}
                    @endif
                  </div>
                </div>
              </div>

              <div style="display:flex;align-items:center;gap:10px">
                {{-- PILLS --}}
                <div style="display:flex;gap:4px;flex-wrap:wrap;
                            justify-content:flex-end">
                  @if($type->is_paid)
                    <span class="leave-badge leave-badge-green">Paid</span>
                  @else
                    <span class="leave-badge leave-badge-gray">Unpaid</span>
                  @endif

                  @if($type->requires_approval)
                    <span class="leave-badge leave-badge-blue">Approval</span>
                  @else
                    <span class="leave-badge leave-badge-gray">Auto</span>
                  @endif

                  @if(($type->max_carry_forward ?? 0) > 0)
                    <span class="leave-badge leave-badge-blue">Carry fwd</span>
                  @endif

                  @if(!$type->is_active)
                    <span class="leave-badge leave-badge-gray">Inactive</span>
                  @endif
                </div>

                {{-- EDIT BUTTON --}}
                <button class="leave-btn"
                        onclick="openEdit({{ $type->id }},
                          '{{ addslashes($type->name) }}',
                          '{{ $type->code }}',
                          {{ $type->days_per_year }},
                          {{ $type->max_carry_forward ?? 0 }},
                          {{ $type->is_paid ? 'true' : 'false' }},
                          {{ $type->requires_approval ? 'true' : 'false' }},
                          {{ ($type->allow_carry_forward ?? false) ? 'true' : 'false' }})">
                  Edit
                </button>
              </div>
            </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>{{-- end right --}}

  </div>{{-- end grid --}}

</div>

{{-- EDIT MODAL (inline, no JS frameworks) --}}
<div id="editModal"
     style="display:none;position:fixed;inset:0;
            background:rgba(0,0,0,0.4);z-index:9999;
            align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:1.5rem;
              width:480px;max-width:95vw;position:relative">
    <div style="display:flex;justify-content:space-between;
                align-items:center;margin-bottom:1.25rem">
      <div style="font-size:15px;font-weight:500;color:#18181b">
        Edit Leave Type
      </div>
      <button onclick="closeEdit()"
              style="background:none;border:none;font-size:18px;
                     cursor:pointer;color:#9ca3af;line-height:1">✕</button>
    </div>

    <form id="editForm" method="POST" action="">
      @csrf
      @method('PUT')
      <div style="display:grid;grid-template-columns:1fr 1fr;
                  gap:12px;margin-bottom:12px">
        <div>
          <label class="leave-label">Name</label>
          <input type="text" id="edit_name" name="name"
                 class="leave-input" required>
        </div>
        <div>
          <label class="leave-label">Code</label>
          <input type="text" id="edit_code" name="code"
                 class="leave-input" maxlength="5" required
                 style="text-transform:uppercase">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;
                  gap:12px;margin-bottom:12px">
        <div>
          <label class="leave-label">Days / year</label>
          <input type="number" id="edit_days" name="days_per_year"
                 class="leave-input" min="0" required>
        </div>
        <div>
          <label class="leave-label">Max carry forward</label>
          <input type="number" id="edit_carry" name="max_carry_forward"
                 class="leave-input" min="0">
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
        @foreach([
          ['id'=>'edit_paid',     'name'=>'is_paid',           'label'=>'Paid Leave'],
          ['id'=>'edit_approval', 'name'=>'requires_approval',  'label'=>'Requires Approval'],
          ['id'=>'edit_carry_fw', 'name'=>'allow_carry_forward','label'=>'Allow Carry Forward'],
        ] as $tog)
        <label style="display:flex;align-items:center;justify-content:space-between;
                      padding:9px 12px;background:#f9fafb;border-radius:8px;
                      border:0.5px solid #f3f4f6;cursor:pointer">
          <span style="font-size:13px;color:#18181b">{{ $tog['label'] }}</span>
          <input type="hidden" name="{{ $tog['name'] }}" value="0">
          <input type="checkbox" id="{{ $tog['id'] }}" name="{{ $tog['name'] }}"
                 value="1" style="width:16px;height:16px;accent-color:#18181b">
        </label>
        @endforeach
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="leave-btn leave-btn-primary"
                style="flex:1;justify-content:center">
          Save Changes
        </button>
        <button type="button" onclick="closeEdit()"
                class="leave-btn" style="flex:1;text-align:center">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(id, name, code, days, carry, paid, approval, carryFw) {
  var base = "{{ url('leaves/settings/types') }}/";
  document.getElementById('editForm').action = base + id;
  document.getElementById('edit_name').value   = name;
  document.getElementById('edit_code').value   = code;
  document.getElementById('edit_days').value   = days;
  document.getElementById('edit_carry').value  = carry;
  document.getElementById('edit_paid').checked     = paid;
  document.getElementById('edit_approval').checked = approval;
  document.getElementById('edit_carry_fw').checked = carryFw;
  var m = document.getElementById('editModal');
  m.style.display = 'flex';
}
function closeEdit() {
  document.getElementById('editModal').style.display = 'none';
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
</script>

@endsection
