@extends('layouts.app')
@section('title', $org->name . ' — Modules')
@section('content')
<link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">

<div class="page-wrapper">

  {{-- Breadcrumb --}}
  <div class="sa-breadcrumb">
    <a href="{{ route('superadmin.organizations') }}" class="sa-breadcrumb-link">Organizations</a>
    <span class="sa-chevron">›</span>
    <a href="{{ route('superadmin.organizations.show', $org->id) }}" class="sa-breadcrumb-link">{{ $org->name }}</a>
    <span class="sa-chevron">›</span>
    <span class="sa-breadcrumb-current">Module Management</span>
  </div>

  {{-- Header --}}
  <div class="flex-between">
    <div>
      <h1 class="sa-page-title">Module Management</h1>
      <p class="sa-page-subtitle">
        Force-enable or disable any module for <strong>{{ $org->name }}</strong>
        regardless of their plan. Current plan: <strong>{{ ucfirst($plan) }}</strong>
      </p>
    </div>
    <a href="{{ route('superadmin.organizations.show', $org->id) }}" class="sa-btn-back">
      ← Back to Org
    </a>
  </div>

  {{-- Super Admin Notice --}}
  <div class="sa-notice">
    <span class="sa-notice-icon">⚠️</span>
    <p class="sa-notice-text">
      <strong>Super Admin Override:</strong> You can enable any module regardless of plan.
      Modules enabled here override the plan's default.
      The organization's plan is <strong>{{ ucfirst($plan) }}</strong>.
    </p>
  </div>

  {{-- Modules Grid --}}
  @php
    $iconSvgs = [
      'github'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
      'shield'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
      'cpu'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>',
      'alert'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
      'monitor'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
      'chart'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
      'code'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
      'users'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
      'folder'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
      'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
      'check'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
      'bell'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>',
    ];
  @endphp

  <div class="sa-modules-grid">
    @foreach($modules as $module)
    @php
      $iconPath       = $iconSvgs[$module['icon']] ?? $iconSvgs['code'];
      $isEnabled      = $module['is_enabled'];
      $isOverride     = $module['override'];
      $overrideRecord = $module['override_record'] ?? null;
    @endphp

    <div class="sa-module-card">
      <div class="sa-module-icon {{ $isEnabled ? 'active' : '' }}">
        <svg class="{{ $isEnabled ? 'sa-module-icon-svg-active' : 'sa-module-icon-svg-inactive' }}"
             fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
          {!! $iconPath !!}
        </svg>
      </div>

      <div class="sa-module-info">
        <div class="sa-module-title-row">
          <div class="sa-module-title">{{ $module['label'] }}</div>
          @if($isOverride)
            @if($isEnabled)
              <span class="sa-module-badge sa-module-badge-override-on">Override: On</span>
            @else
              <span class="sa-module-badge sa-module-badge-override-off">Override: Off</span>
            @endif
          @elseif($module['included_in_plan'])
            <span class="sa-module-badge sa-module-badge-plan-default">Plan Default</span>
          @else
            <span class="sa-module-badge sa-module-badge-not-in-plan">Not in Plan</span>
          @endif
        </div>

        <div class="sa-module-desc">{{ $module['description'] }}</div>

        @if($overrideRecord)
        <div class="sa-module-meta">
          @if($isEnabled && $overrideRecord->enabled_at)
            Enabled {{ $overrideRecord->enabled_at->diffForHumans() }}
            @if($overrideRecord->enabledBy) by {{ $overrideRecord->enabledBy->name }} @endif
          @elseif(!$isEnabled && $overrideRecord->disabled_at)
            Disabled {{ $overrideRecord->disabled_at->diffForHumans() }}
            @if($overrideRecord->enabledBy) by {{ $overrideRecord->enabledBy->name }} @endif
          @endif
        </div>
        @endif

        <div class="sa-module-actions">
          <form method="POST" action="{{ route('superadmin.org.modules.toggle', [$org->id, $module['name']]) }}">
            @csrf
            <input type="hidden" name="action" value="{{ $isEnabled ? 'disable' : 'enable' }}">
            <button type="submit"
                    class="sa-btn {{ $isEnabled ? 'sa-btn-disable' : 'sa-btn-enable' }}"
                    data-confirm="{{ $isEnabled ? 'Disable' : 'Force-enable' }} {{ $module['label'] }} for {{ $org->name }}?">
              {{ $isEnabled ? 'Disable' : 'Force Enable' }}
            </button>
          </form>
          @if($isOverride)
          <span class="sa-module-override-note">Override active — overrides plan default</span>
          @endif
        </div>
      </div>
    </div>
    @endforeach
  </div>

</div>
@endsection
