@extends('layouts.app')
@section('title', 'Module Management')
@section('content')

<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Module Management</h1>
        <p class="page-subtitle">Control which features are active for your organization</p>
    </div>
</div>

        {{-- Alerts --}}
        @if(session('success'))
        <div class="fv-alert fv-alert-success anim-fade-up">
            <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="fv-alert fv-alert-error anim-fade-up">{{ session('error') }}</div>
        @endif

        {{-- Current Plan Card --}}
        <div class="fv-card anim-fade-up" style="padding:24px 28px;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="width:48px; height:48px; background:var(--surface); border:1px solid var(--border); border-radius:12px;
                                display:flex; align-items:center; justify-content:center;">
                        <svg style="width:22px; height:22px; color:var(--text-2);" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--text-4); text-transform:uppercase; letter-spacing:0.06em;">Current Plan</div>
                        <div style="font-size:1.25rem; font-weight:800; color:var(--text); margin-top:2px;">
                            {{ ucfirst($plan) }}
                            @if($plan === 'free')
                                <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px; background:#f4f4f5; color:#71717a; border:1px solid #e4e4e7; margin-left:6px; vertical-align:middle;">Free</span>
                            @elseif($plan === 'pro')
                                <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px; background:#f4f4f5; color:#18181b; border:1px solid #e4e4e7; margin-left:6px; vertical-align:middle;">Pro</span>
                            @else
                                <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px; background:#18181b; color:white; border:1px solid #18181b; margin-left:6px; vertical-align:middle;">Enterprise</span>
                            @endif
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-4); margin-top:2px;">{{ $org->name }}</div>
                    </div>
                </div>
                @if($plan === 'free')
                <div class="text-right">
                    <div style="font-size:0.85rem; color:#6b7280; margin-bottom:8px;">
                        Upgrade to unlock more modules
                    </div>
                    <a href="{{ route('billing.index') }}" class="fv-btn fv-btn-primary" style="font-size:0.82rem;">
                        Upgrade Plan →
                    </a>
                </div>
                @elseif($plan === 'pro')
                <div class="text-right">
                    <div style="font-size:0.85rem; color:#6b7280; margin-bottom:8px;">
                        Manage your subscription
                    </div>
                    <a href="{{ route('billing.index') }}" class="fv-btn fv-btn-primary" style="font-size:0.82rem;">
                        Manage Billing →
                    </a>
                </div>
                @endif
                {{-- Enterprise: show nothing (already top tier) --}}
            </div>
        </div>

        {{-- Modules Grid --}}
        @php
        $iconSvgs = [
            'github'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
            'shield'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
            'cpu'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>',
            'alert'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
            'monitor' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
            'chart'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            'code'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
        ];
        $planOrder = ['free' => 1, 'pro' => 2, 'enterprise' => 3];
        @endphp

        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:16px;" class="anim-fade-up delay-1">
            @foreach($modules as $module)
            @php
                $iconPath  = $iconSvgs[$module['icon']] ?? $iconSvgs['code'];
                $planReq   = $module['plan_required'];
                $canToggle = $module['included_in_plan'] || $module['override'];
                $isEnabled = $module['is_enabled'];
            @endphp
            <div class="fv-card" style="padding:20px 24px; display:flex; align-items:flex-start; gap:16px;
                        {{ $isEnabled ? '' : 'opacity:0.75;' }}">

                {{-- Icon --}}
                <div style="width:44px; height:44px; border-radius:12px; flex-shrink:0;
                            background:{{ $isEnabled ? 'var(--text)' : 'var(--surface)' }};
                            border:1px solid {{ $isEnabled ? 'var(--text)' : 'var(--border)' }};
                            display:flex; align-items:center; justify-content:center;">
                    <svg style="width:20px; height:20px; color:{{ $isEnabled ? 'white' : 'var(--text-4)' }};"
                         fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        {!! $iconPath !!}
                    </svg>
                </div>

                {{-- Info --}}
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                        <div style="font-size:0.9rem; font-weight:700; color:var(--text);">{{ $module['label'] }}</div>
                        {{-- Status badge --}}
                        @if($module['override'] && $isEnabled)
                            <span class="fv-badge fv-badge-gray" style="background:#18181b;color:white;border-color:#18181b;font-size:0.65rem;">Enabled</span>
                        @elseif($module['override'] && !$isEnabled)
                            <span class="fv-badge fv-badge-gray" style="font-size:0.65rem;">Disabled</span>
                        @elseif($module['included_in_plan'])
                            <span class="fv-badge fv-badge-green" style="font-size:0.65rem;">Included</span>
                        @else
                            <span class="fv-badge fv-badge-warning" style="font-size:0.65rem;">Upgrade Required</span>
                        @endif
                    </div>
                    <div style="font-size:0.8rem; color:var(--text-3); line-height:1.5; margin-bottom:12px;">{{ $module['description'] }}</div>

                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        @if($canToggle)
                            <form method="POST" action="{{ route('admin.modules.toggle', $module['name']) }}">
                                @csrf
                                <input type="hidden" name="action" value="{{ $isEnabled ? 'disable' : 'enable' }}">
                                <button type="submit" class="fv-btn {{ $isEnabled ? 'fv-btn-secondary' : 'fv-btn-primary' }}"
                                        style="font-size:0.78rem; padding:5px 14px;"
                                        onclick="return confirm('{{ $isEnabled ? 'Disable' : 'Enable' }} {{ $module['label'] }}?')">
                                    {{ $isEnabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        @else
                            @php
                                $upgradeLabel = $planReq === 'enterprise' ? 'Enterprise' : 'Pro';
                            @endphp
                            <button type="button" class="fv-btn fv-btn-secondary" style="font-size:0.78rem; padding:5px 14px; opacity:0.5; cursor:not-allowed;" disabled>
                                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Requires {{ $upgradeLabel }}
                            </button>
                        @endif

                        @if($module['override'])
                            <span style="font-size:0.72rem; color:var(--text-4);">
                                Override active
                                @if($module['enabled_at'])
                                    · {{ $module['enabled_at']->diffForHumans() }}
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Plan Comparison Table --}}
        <div class="fv-card anim-fade-up delay-2">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Plan Comparison</div>
                    <div class="fv-section-sub">Which modules are included in each plan</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="text-center">Free</th>
                            <th class="text-center">Pro</th>
                            <th class="text-center">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $planMap = [
                            'github_sync'       => ['free' => true,  'pro' => true,  'enterprise' => true],
                            'employee_directory'=> ['free' => true,  'pro' => true,  'enterprise' => true],
                            'document_center'   => ['free' => true,  'pro' => true,  'enterprise' => true],
                            'leave_management'  => ['free' => true,  'pro' => true,  'enterprise' => true],
                            'onboarding'        => ['free' => true,  'pro' => true,  'enterprise' => true],
                            'announcements'     => ['free' => true,  'pro' => true,  'enterprise' => true],
                            'fairness_engine'   => ['free' => false, 'pro' => true,  'enterprise' => true],
                            'ai_intelligence'   => ['free' => false, 'pro' => true,  'enterprise' => true],
                            'reports'           => ['free' => false, 'pro' => true,  'enterprise' => true],
                            'hr_reports'        => ['free' => false, 'pro' => true,  'enterprise' => true],
                            'blockers'          => ['free' => false, 'pro' => true,  'enterprise' => true],
                            'command_center'    => ['free' => false, 'pro' => false, 'enterprise' => true],
                            'api_access'        => ['free' => false, 'pro' => false, 'enterprise' => true],
                        ];
                        $moduleLabels = [
                            'github_sync'       => 'GitHub Sync',
                            'employee_directory'=> 'Employee Directory',
                            'document_center'   => 'Document Center',
                            'leave_management'  => 'Leave Management',
                            'onboarding'        => 'Onboarding',
                            'announcements'     => 'Announcements',
                            'fairness_engine'   => 'Fairness Engine',
                            'ai_intelligence'   => 'AI Intelligence',
                            'reports'           => 'Reports & Analytics',
                            'hr_reports'        => 'HR Reports',
                            'blockers'          => 'Blockers & Dependencies',
                            'command_center'    => 'Command Center',
                            'api_access'        => 'API Access',
                        ];
                        @endphp
                        @foreach($planMap as $mod => $plans)
                        <tr>
                            <td style="font-weight:600; color:var(--text);">{{ $moduleLabels[$mod] }}</td>
                            @foreach(['free', 'pro', 'enterprise'] as $p)
                            <td class="text-center">
                                @if($plans[$p])
                                    <svg style="width:18px;height:18px;color:#16a34a;display:inline-block;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg style="width:18px;height:18px;color:#cbd5e1;display:inline-block;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

</div>
@endsection
