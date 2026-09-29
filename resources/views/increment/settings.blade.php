@extends('layouts.app')
@section('title', 'Increment Settings')
@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Increment Policy Settings</h1>
        <p class="page-subtitle">Define how your organization calculates employee increments</p>
    </div>
</div>

    @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:20px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('increment.save-policy') }}" id="policyForm">
        @csrf

        {{-- Section 1: Policy Basics --}}
        <div style="background:#fff;border:1px solid #e4e4e7;border-radius:12px;padding:24px;margin-bottom:20px;">
            <h2 style="font-size:16px;font-weight:600;color:#18181b;margin:0 0 20px;">Policy Basics</h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:6px;">Policy Name</label>
                    <input type="text" name="name" value="{{ old('name', $policy?->name ?? date('Y').' Increment Policy') }}"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:6px;">Maximum Increment %</label>
                    <div style="position:relative;">
                        <input type="number" name="max_increment_percent" id="maxIncrement" step="0.01" min="1" max="100"
                            value="{{ old('max_increment_percent', $policy?->max_increment_percent ?? 30) }}"
                            style="width:100%;padding:8px 36px 8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#71717a;font-size:14px;">%</span>
                    </div>
                    <p style="font-size:11px;color:#71717a;margin:4px 0 0;">Maximum any employee can receive</p>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:6px;">Review Period</label>
                    <select name="review_period" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                        <option value="annual" {{ ($policy?->review_period ?? 'annual') === 'annual' ? 'selected' : '' }}>Annual</option>
                        <option value="semi_annual" {{ ($policy?->review_period) === 'semi_annual' ? 'selected' : '' }}>Semi-Annual</option>
                        <option value="quarterly" {{ ($policy?->review_period) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:6px;">Minimum Months Required</label>
                    <input type="number" name="minimum_months_required" min="3" max="12"
                        value="{{ old('minimum_months_required', $policy?->minimum_months_required ?? 6) }}"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:6px;">Minimum Score for Increment</label>
                    <input type="number" name="minimum_score_for_increment" min="0" max="100" step="0.1"
                        value="{{ old('minimum_score_for_increment', $policy?->minimum_score_for_increment ?? 40) }}"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    <p style="font-size:11px;color:#71717a;margin:4px 0 0;">Below this score = 0% increment</p>
                </div>
            </div>

            {{-- Anti Gaming Toggle --}}
            <div style="display:flex;justify-content:space-between;
              align-items:center;padding:16px 0;
              border-bottom:1px solid #f4f4f5;">
              <div style="flex:1;">
                <div style="font-size:0.875rem;font-weight:600;
                  color:#09090b;">
                  Anti-Gaming Detection
                </div>
                <div style="font-size:0.78rem;color:#71717a;
                  margin-top:4px;">
                  Detects and penalizes suspicious activity
                  spikes before review periods.
                </div>
              </div>
              <div style="display:flex;align-items:center;
                gap:12px;flex-shrink:0;margin-left:24px;">
                <input type="hidden"
                  name="anti_gaming_enabled"
                  id="anti_gaming_hidden"
                  value="{{ old('anti_gaming_enabled', $policy?->anti_gaming_enabled ?? true) ? '1' : '0' }}">
                <button type="button"
                  id="anti_gaming_btn"
                  onclick="toggleGaming()"
                  style="width:52px;height:28px;
                  border-radius:99px;border:none;
                  cursor:pointer;position:relative;
                  transition:background 0.2s;
                  background:{{ old('anti_gaming_enabled', $policy?->anti_gaming_enabled ?? true) ? '#18181b' : '#d4d4d8' }};">
                  <span id="anti_gaming_knob"
                    style="position:absolute;
                    width:22px;height:22px;
                    background:white;border-radius:50%;
                    top:3px;transition:left 0.2s;
                    box-shadow:0 1px 3px rgba(0,0,0,0.3);
                    left:{{ old('anti_gaming_enabled', $policy?->anti_gaming_enabled ?? true) ? '27px' : '3px' }};">
                  </span>
                </button>
                <span id="anti_gaming_text"
                  style="font-size:0.85rem;font-weight:600;
                  min-width:60px;
                  color:{{ old('anti_gaming_enabled', $policy?->anti_gaming_enabled ?? true) ? '#16a34a' : '#71717a' }};">
                  {{ old('anti_gaming_enabled', $policy?->anti_gaming_enabled ?? true) ? 'Enabled' : 'Disabled' }}
                </span>
              </div>
            </div>
        </div>

        {{-- Section 2: Criteria Builder --}}
        <div style="background:#fff;border:1px solid #e4e4e7;border-radius:12px;padding:24px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                <div>
                    <h2 style="font-size:16px;font-weight:600;color:#18181b;margin:0 0 4px;">Increment Criteria</h2>
                    <p style="font-size:13px;color:#71717a;margin:0;">Define what factors contribute. Total weight must equal maximum increment %</p>
                </div>
                <div id="weightCounter" style="font-size:14px;font-weight:600;padding:6px 14px;border-radius:20px;background:#f4f4f5;color:#18181b;">
                    Total: <span id="totalWeight">0</span>% / <span id="maxLabel">30</span>%
                </div>
            </div>

            {{-- Preset Templates --}}
            <div style="margin-bottom:20px;">
                <p style="font-size:12px;font-weight:500;color:#71717a;margin:0 0 8px;">Quick presets:</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @foreach(['Engineering','Sales','HR','Finance','Operations'] as $preset)
                    <button type="button" onclick="loadPreset('{{ strtolower($preset) }}')"
                        style="padding:5px 12px;border:1px solid #e4e4e7;border-radius:20px;font-size:12px;background:#fff;cursor:pointer;">
                        {{ $preset }} Team
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Criteria list --}}
            <div id="criteriaList" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
                @if($policy && $policy->criteria->count())
                    @foreach($policy->criteria as $i => $c)
                    <div class="criteria-row" style="display:grid;grid-template-columns:2fr 1fr 2fr 100px 36px;gap:10px;align-items:center;">
                        <input type="text" name="criteria[{{ $i }}][label]" value="{{ $c->criteria_label }}" placeholder="Label"
                            style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
                        <select name="criteria[{{ $i }}][type]" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
                            <option value="automatic" {{ $c->criteria_type==='automatic'?'selected':'' }}>Automatic</option>
                            <option value="manual" {{ $c->criteria_type==='manual'?'selected':'' }}>Manual</option>
                        </select>
                        <select name="criteria[{{ $i }}][source]" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
                            <option value="">— Source —</option>
                            @foreach(['github_commits','github_prs','work_logs','tasks_completed','task_complexity','blocker_resolution','attendance'] as $src)
                            <option value="{{ $src }}" {{ $c->data_source===$src?'selected':'' }}>{{ str_replace('_',' ',ucfirst($src)) }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="criteria[{{ $i }}][name]" value="{{ $c->criteria_name }}">
                        <div style="position:relative;">
                            <input type="number" name="criteria[{{ $i }}][weight]" value="{{ $c->weight_percent }}" step="0.5" min="0"
                                class="weight-input" onchange="updateTotal()"
                                style="width:100%;padding:8px 22px 8px 8px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                            <span style="position:absolute;right:7px;top:50%;transform:translateY(-50%);color:#71717a;font-size:12px;">%</span>
                        </div>
                        <button type="button" onclick="this.closest('.criteria-row').remove();updateTotal()"
                            style="width:36px;height:36px;border:1px solid #fecaca;border-radius:8px;background:#fef2f2;color:#dc2626;cursor:pointer;font-size:16px;">×</button>
                    </div>
                    @endforeach
                @endif
            </div>

            <button type="button" onclick="addCriteria()"
                style="padding:8px 16px;border:1px dashed #d1d5db;border-radius:8px;background:#fafafa;font-size:13px;cursor:pointer;color:#374151;">
                + Add Criteria
            </button>
        </div>

        {{-- Save --}}
        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <button type="submit"
                style="padding:10px 28px;background:#18181b;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                Save Policy
            </button>
        </div>

        <script>
        var antiGamingEnabled = {{ old('anti_gaming_enabled', $policy?->anti_gaming_enabled ?? true) ? 'true' : 'false' }};

        function toggleGaming() {
            antiGamingEnabled = !antiGamingEnabled;
            var btn    = document.getElementById('anti_gaming_btn');
            var knob   = document.getElementById('anti_gaming_knob');
            var text   = document.getElementById('anti_gaming_text');
            var hidden = document.getElementById('anti_gaming_hidden');
            if (antiGamingEnabled) {
                btn.style.background = '#18181b';
                knob.style.left = '27px';
                text.textContent = 'Enabled';
                text.style.color = '#16a34a';
                hidden.value = '1';
            } else {
                btn.style.background = '#d4d4d8';
                knob.style.left = '3px';
                text.textContent = 'Disabled';
                text.style.color = '#71717a';
                hidden.value = '0';
            }
        }
        </script>
    </form>

    {{-- Current Policy Summary --}}
    @if($policy)
    <div style="background:#f8fafc;border:1px solid #e4e4e7;border-radius:12px;padding:20px;margin-top:20px;">
        <h3 style="font-size:14px;font-weight:600;color:#18181b;margin:0 0 12px;">Current Active Policy</h3>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            <div><span style="font-size:11px;color:#71717a;display:block;">Max Increment</span><span style="font-size:16px;font-weight:700;color:#18181b;">{{ $policy->max_increment_percent }}%</span></div>
            <div><span style="font-size:11px;color:#71717a;display:block;">Criteria</span><span style="font-size:16px;font-weight:700;color:#18181b;">{{ $policy->criteria->count() }}</span></div>
            <div><span style="font-size:11px;color:#71717a;display:block;">Review Period</span><span style="font-size:16px;font-weight:700;color:#18181b;">{{ ucfirst(str_replace('_',' ',$policy->review_period)) }}</span></div>
            <div><span style="font-size:11px;color:#71717a;display:block;">Status</span><span style="font-size:12px;font-weight:600;padding:3px 8px;border-radius:20px;background:#f0fdf4;color:#166534;">Active</span></div>
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>window.CRITERIA_COUNT={{ $policy ? $policy->criteria->count() : 0 }};</script>
<script src="{{ asset('js/increment-settings.js') }}"></script>
@endpush
