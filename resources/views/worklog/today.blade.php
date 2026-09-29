@extends('layouts.app')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 24px;">

{{-- Header --}}
<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
    <div>
        <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:4px;">
            {{ now()->format('l, F j') }}
        </div>
        <h1 style="font-size:1.75rem;font-weight:800;color:#0f172a;letter-spacing:-0.025em;margin-bottom:4px;">Today's Work Log</h1>
        <p style="color:#94a3b8;font-size:0.9rem;margin:0;">Log what you worked on today</p>
    </div>
    <a href="{{ route('worklog.index') }}" class="fv-btn fv-btn-secondary" style="font-size:0.82rem;">← All Logs</a>
</div>

@if(session('success'))
<div class="fv-alert fv-alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif

{{-- Today's summary cards --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="fv-card" style="padding:18px;border-top:3px solid #18181b;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Hours Logged</div>
        <div style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ number_format($todayMinutes / 60, 1) }}<span style="font-size:1rem;color:#94a3b8;">h</span></div>
    </div>
    <div class="fv-card" style="padding:18px;border-top:3px solid #059669;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Activities</div>
        <div style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ $todayLogs->count() }}</div>
    </div>
    <div class="fv-card" style="padding:18px;border-top:3px solid #d97706;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Avg Output</div>
        <div style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ number_format($todayOutput, 1) }}<span style="font-size:1rem;color:#94a3b8;">/10</span></div>
    </div>
</div>

{{-- Quick log form --}}
<div class="fv-card" style="padding:24px;margin-bottom:24px;">
    <div style="font-size:1rem;font-weight:800;color:#0f172a;margin-bottom:16px;">Log an Activity</div>
    <form method="POST" action="{{ route('worklog.store') }}">
        @csrf
        <input type="hidden" name="log_date" value="{{ today()->toDateString() }}">
        <input type="hidden" name="redirect" value="today">

        <div style="display:grid;grid-template-columns:200px 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;">Category</label>
                <select name="category" required style="width:100%;padding:10px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.85rem;font-family:inherit;background:white;color:#0f172a;">
                    <option value="">Select…</option>
                    @foreach(['meeting'=>'Meeting','code_review'=>'Code Review','development'=>'Development','research'=>'Research','support'=>'Support','training'=>'Training','travel'=>'Travel','client_call'=>'Client Call','vendor_call'=>'Vendor Call','planning'=>'Planning','documentation'=>'Documentation','design'=>'Design','testing'=>'Testing','reporting'=>'Reporting','recruitment'=>'Recruitment','other'=>'Other'] as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;">What did you do? *</label>
                <input type="text" name="title" placeholder="e.g. Reviewed PR #42, Fixed login bug, Sprint planning meeting…" required
                       style="width:100%;padding:10px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.88rem;font-family:inherit;color:#0f172a;box-sizing:border-box;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:140px 140px 1fr auto;gap:12px;align-items:flex-end;">
            <div>
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;">Duration (mins)</label>
                <input type="number" name="duration_minutes" min="1" max="480" placeholder="e.g. 90"
                       style="width:100%;padding:10px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.85rem;font-family:inherit;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;">Output (1–10)</label>
                <input type="number" name="output_value" min="1" max="10" value="5"
                       style="width:100%;padding:10px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.85rem;font-family:inherit;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;">Project (optional)</label>
                <select name="project_id" style="width:100%;padding:10px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:0.85rem;font-family:inherit;background:white;color:#0f172a;">
                    <option value="">No project</option>
                    @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="padding-bottom:1px;">
                <button type="submit" class="fv-btn fv-btn-primary" style="white-space:nowrap;">+ Log Activity</button>
            </div>
        </div>
    </form>
</div>

{{-- Today's entries --}}
<div class="fv-card" class="mb-lg">
    <div class="fv-section-header">
        <div>
            <div class="fv-section-title">Today's Activities</div>
            <div class="fv-section-sub">{{ $todayLogs->count() }} entries · {{ number_format($todayMinutes/60,1) }}h total</div>
        </div>
        @if($todayLogs->isNotEmpty())
        <button onclick="generateSummary()" style="font-size:0.78rem;font-weight:700;padding:6px 14px;border:1px solid #e4e4e7;border-radius:8px;background:white;cursor:pointer;color:#374151;font-family:inherit;"
                onmouseover="this.style.borderColor='#a1a1aa'" onmouseout="this.style.borderColor='#e4e4e7'">
            ✨ AI Summary
        </button>
        @endif
    </div>

    <div id="ai-summary" style="display:none;padding:16px 20px;background:#f0fdf4;border-left:3px solid #059669;margin:0 20px 12px;border-radius:4px;font-size:0.85rem;color:#065f46;line-height:1.6;"></div>

    @if($todayLogs->isEmpty())
    <div class="fv-empty">
        <div class="fv-empty-title">Nothing logged yet</div>
        <div class="fv-empty-desc">Use the form above to log your first activity for today.</div>
    </div>
    @else
    @foreach($todayLogs as $log)
    @php $catColor = $log->getCategoryColor(); @endphp
    <div style="padding:14px 20px;border-bottom:1px solid #f8fafc;display:flex;align-items:center;gap:14px;">
        <div style="width:4px;height:40px;background:{{ $catColor }};border-radius:2px;flex-shrink:0;"></div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:0.88rem;font-weight:700;color:#0f172a;margin-bottom:3px;">{{ $log->title }}</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <span style="font-size:0.7rem;font-weight:700;padding:1px 7px;border-radius:10px;background:{{ $catColor }}18;color:{{ $catColor }};">{{ $log->getCategoryLabel() }}</span>
                @if($log->duration_minutes)
                <span style="font-size:0.75rem;color:#64748b;">{{ $log->getDurationFormatted() }}</span>
                @endif
                @if($log->output_value)
                <span style="font-size:0.72rem;font-weight:700;color:#d97706;">★ {{ $log->output_value }}/10</span>
                @endif
                @if($log->evidence)
                <span style="font-size:0.72rem;color:#94a3b8;font-family:monospace;">{{ Str::limit($log->evidence, 40) }}</span>
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('worklog.destroy', $log->id) }}"
              onsubmit="return confirm('Delete this entry?');">
            @csrf @method('DELETE')
            <button type="submit" style="font-size:0.75rem;color:#94a3b8;background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:4px;font-family:inherit;"
                    onmouseover="this.style.color='#dc2626';this.style.background='#fef2f2'"
                    onmouseout="this.style.color='#94a3b8';this.style.background='none'">✕</button>
        </form>
    </div>
    @endforeach
    @endif
</div>

</div>

@push('scripts')
<script>
function generateSummary() {
    const btn = event.target;
    const container = document.getElementById('ai-summary');
    btn.disabled = true;
    btn.textContent = '⏳ Generating…';
    container.style.display = 'block';
    container.textContent = 'Analyzing your day…';

    fetch('{{ route("worklog.summary") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ date: '{{ today()->toDateString() }}' }),
    })
    .then(r => r.json())
    .then(data => {
        container.textContent = data.summary;
        btn.disabled = false;
        btn.textContent = '✨ AI Summary';
    })
    .catch(() => {
        container.textContent = 'Could not generate summary. Try again.';
        btn.disabled = false;
        btn.textContent = '✨ AI Summary';
    });
}
</script>
@endpush
@endsection
