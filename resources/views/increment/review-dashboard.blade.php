@extends('layouts.app')
@section('title', 'Increment Reviews')
@section('content')
<div class="page-wrapper">

@if(session('info'))
<div class="alert-info mb-md">&#8505;&#65039; {{ session('info') }}</div>
@endif

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Increment Review Dashboard</h1>
        <p class="page-subtitle">{{ $policy->name }} &middot; Max {{ $policy->max_increment_percent }}%</p>
    </div>
    <div class="page-header-right">
        <form method="GET">
            <select name="year" onchange="this.form.submit()"
                style="padding:8px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:13px;">
                @for($y=date('Y');$y>=date('Y')-3;$y--)
                    <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
        <form method="POST" action="{{ route('increment.calculate') }}">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <button type="submit" class="btn-primary">
                &#9889; Calculate {{ $year }}
            </button>
        </form>
        <form method="POST" action="{{ route('increment.calculate-month') }}" style="display:flex;gap:8px;">
            @csrf
            <input type="month" name="month" value="{{ now()->format('Y-m') }}"
                style="padding:7px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:13px;">
            <button type="submit" class="btn-secondary">
                Calc Month
            </button>
        </form>
    </div>
</div>

    @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    @if($suspiciousOverrides->count())
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:16px;margin-bottom:20px;">
        <p style="font-weight:600;color:#92400e;margin:0 0 8px;">&#9888;&#65039; {{ $suspiciousOverrides->count() }} suspicious override(s) detected</p>
        @foreach($suspiciousOverrides as $s)
        <p style="font-size:13px;color:#78350f;margin:2px 0;">• {{ $s['name'] }}: System recommended {{ $s['recommended_increment'] }}%, final {{ $s['final_increment'] }}%</p>
        @endforeach
    </div>
    @endif

    @if($reviews->isEmpty())
    <div style="background:#fff;border:1px solid #e4e4e7;border-radius:12px;">
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 40px;text-align:center;">
            <div style="width:72px;height:72px;background:#f4f4f5;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:24px;">📊</div>
            <h3 style="font-size:18px;font-weight:700;color:#18181b;margin:0 0 8px;">No increment reviews yet</h3>
            <p style="font-size:14px;color:#71717a;max-width:380px;line-height:1.6;margin:0 0 28px;">Increment reviews for {{ $year }} haven't been generated yet. Click "Calculate {{ $year }}" to run the AI-powered 10-layer increment analysis for your team.</p>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:center;">
                <div style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#f4f4f5;border-radius:99px;font-size:12px;font-weight:600;color:#71717a;">⚡ 10-layer AI analysis</div>
                <div style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#f4f4f5;border-radius:99px;font-size:12px;font-weight:600;color:#71717a;">🛡️ Anti-gaming system</div>
                <div style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#f4f4f5;border-radius:99px;font-size:12px;font-weight:600;color:#71717a;">📈 Fair increment scores</div>
            </div>
        </div>
    </div>
    @else
    <div style="background:#fff;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f4f4f5;border-bottom:1px solid #e4e4e7;">
                    <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:#71717a;">EMPLOYEE</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">SCORE</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">RATING</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">SYSTEM %</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">MANAGER %</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">FINAL %</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">STATUS</th>
                    <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:#71717a;">ACTION</th>
                </tr>
            </thead>
            <tbody id="increment-reviews-list">
                @foreach($reviews as $r)
                <tr style="border-bottom:1px solid #f4f4f5;" x-data="{ open: false }">
                    <td style="padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:#18181b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0;">{{ $r['avatar'] }}</div>
                            <div>
                                <div style="font-size:14px;font-weight:600;color:#18181b;">{{ $r['name'] }}</div>
                                <div style="font-size:12px;color:#71717a;">{{ $r['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        <span style="font-size:18px;font-weight:700;color:#18181b;">{{ $r['avg_score'] }}</span>
                        <span style="font-size:11px;color:#71717a;display:block;">{{ $r['score_label'] }}</span>
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        <span style="font-size:18px;">{{ str_repeat('&#11088;',$r['star_rating']) }}{{ str_repeat('&#9734;',5-$r['star_rating']) }}</span>
                    </td>
                    <td style="padding:14px 16px;text-align:center;font-size:15px;font-weight:600;color:#0369a1;">{{ $r['recommended_increment'] }}%</td>
                    <td style="padding:14px 16px;text-align:center;font-size:15px;font-weight:600;color:#7c3aed;">{{ $r['manager_recommendation'] ? $r['manager_recommendation'].'%' : '—' }}</td>
                    <td style="padding:14px 16px;text-align:center;">
                        @if($r['final_increment'] !== null)
                            <span style="font-size:16px;font-weight:700;color:#166534;">{{ $r['final_increment'] }}%</span>
                            @if($r['is_overridden'])<span style="font-size:10px;color:#d97706;display:block;">&#9888;&#65039; Overridden</span>@endif
                        @else
                            <span style="color:#71717a;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        @php $statusColors=['pending'=>'#f4f4f5|#71717a','manager_reviewed'=>'#eff6ff|#1d4ed8','ceo_approved'=>'#f0fdf4|#166534','appealed'=>'#fff7ed|#c2410c'];
                        [$bg,$color] = explode('|',$statusColors[$r['status']]??'#f4f4f5|#71717a'); @endphp
                        <span style="background:{{ $bg }};color:{{ $color }};padding:3px 8px;border-radius:20px;font-size:11px;font-weight:600;">{{ ucfirst(str_replace('_',' ',$r['status'])) }}</span>
                        @if($r['has_appeal'])<span style="font-size:10px;color:#d97706;display:block;">Appeal pending</span>@endif
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        @if($r['status'] === 'pending' || $r['status'] === 'manager_reviewed')
                        <button @click="open=!open"
                            style="padding:6px 12px;background:#18181b;color:#fff;border:none;border-radius:6px;font-size:12px;cursor:pointer;">
                            Approve
                        </button>
                        @endif
                    </td>
                </tr>
                {{-- Approval inline form --}}
                <tr x-show="open" x-data="{ open: false }" style="background:#fafafa;border-bottom:1px solid #e4e4e7;">
                    <td colspan="8" style="padding:16px 20px;">
                        <form method="POST" action="{{ route('increment.approve', $r['id']) }}">
                            @csrf
                            <div style="display:grid;grid-template-columns:120px 1fr 1fr auto;gap:12px;align-items:end;">
                                <div>
                                    <label style="font-size:12px;color:#71717a;display:block;margin-bottom:4px;">Final Increment %</label>
                                    <input type="number" name="final_increment" step="0.1" min="0" max="{{ $policy->max_increment_percent }}"
                                        value="{{ $r['recommended_increment'] }}" id="final_{{ $r['id'] }}"
                                        oninput="checkOverride({{ $r['id'] }},{{ $r['recommended_increment'] }},this.value)"
                                        style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="font-size:12px;color:#71717a;display:block;margin-bottom:4px;">CEO Notes (optional)</label>
                                    <input type="text" name="ceo_notes" placeholder="Add notes..."
                                        style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                                </div>
                                <div id="override_{{ $r['id'] }}" style="display:none;">
                                    <label style="font-size:12px;color:#d97706;display:block;margin-bottom:4px;">&#9888;&#65039; Override reason (required)</label>
                                    <input type="text" name="override_reason" placeholder="Reason for override..."
                                        style="width:100%;padding:8px;border:1px solid #fde68a;border-radius:8px;font-size:13px;box-sizing:border-box;">
                                </div>
                                <button type="submit" style="padding:8px 20px;background:#166534;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                                    &#10003; Approve
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div id="increment-reviews-pagination" class="pagination-wrapper"></div>

    @if(session('results'))
    <div style="background:#fff;border:1px solid #e4e4e7;border-radius:12px;padding:20px;margin-top:20px;">
        <h3 style="font-size:14px;font-weight:600;margin:0 0 12px;">Calculation Results</h3>
        @foreach(session('results') as $r)
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f4f4f5;font-size:13px;">
            <span>{{ $r['user'] }}</span>
            @if($r['status']==='calculated')
                <span style="color:#166534;">Score: {{ round($r['score'],1) }} → {{ $r['increment'] }}% increment</span>
            @else
                <span style="color:#dc2626;">{{ $r['error'] }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/increments.js') }}"></script>
<script src="{{ asset('js/increment-reviews-page.js') }}"></script>
@endpush
