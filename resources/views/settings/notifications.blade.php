@extends('layouts.app')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div>

            @if(session('success'))
            <div style="background:#f0fdf4;color:#16a34a;border:1px solid rgba(22,163,74,0.2);padding:12px 16px;border-radius:8px;font-size:0.875rem;font-weight:500;margin-bottom:20px;">
                ✓ {{ session('success') }}
            </div>
            @endif

            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Email Notifications</h2>
                    <p style="font-size:0.8rem;color:#71717a;margin-top:4px;">Choose which emails you want to receive</p>
                </div>

                <form method="POST" action="{{ route('settings.notifications.update') }}">
                    @csrf

                    @php
                    $n = $settings['notifications'] ?? [];
                    $items = [
                        ['key' => 'email_flags',         'label' => 'Fairness Flag Alerts',  'desc' => 'Email when new fairness flags are detected'],
                        ['key' => 'email_blockers',       'label' => 'Blocker Notifications', 'desc' => 'Email when blockers are reported or resolved'],
                        ['key' => 'email_weekly_digest',  'label' => 'Weekly Team Digest',    'desc' => 'Weekly summary every Monday morning'],
                        ['key' => 'email_team_joins',     'label' => 'Team Member Joins',     'desc' => 'Email when someone joins your organization'],
                    ];
                    @endphp

                    <div style="padding:8px 0;">
                        @foreach($items as $item)
                        @php $isOn = !empty($n[$item['key']]); @endphp
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;border-bottom:1px solid #f4f4f5;">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:#09090b;">{{ $item['label'] }}</div>
                                <div style="font-size:0.78rem;color:#71717a;margin-top:2px;">{{ $item['desc'] }}</div>
                            </div>
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;flex-shrink:0;margin-left:32px;">
                                <div style="position:relative;">
                                    <input type="checkbox"
                                        id="notif_{{ $item['key'] }}"
                                        name="{{ $item['key'] }}"
                                        value="1"
                                        {{ $isOn ? 'checked' : '' }}
                                        style="display:none;">
                                    <div id="notif_track_{{ $item['key'] }}"
                                        onclick="toggleNotif('{{ $item['key'] }}')"
                                        style="width:48px;height:26px;border-radius:99px;cursor:pointer;
                                               transition:background 0.2s ease;position:relative;
                                               background:{{ $isOn ? '#18181b' : '#e4e4e7' }};">
                                        <div id="notif_knob_{{ $item['key'] }}"
                                            style="width:20px;height:20px;background:white;border-radius:50%;
                                                   position:absolute;top:3px;transition:left 0.2s ease;
                                                   left:{{ $isOn ? '25px' : '3px' }};
                                                   box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                        </div>
                                    </div>
                                </div>
                                <span id="notif_label_{{ $item['key'] }}"
                                    style="font-size:0.875rem;font-weight:600;
                                           color:{{ $isOn ? '#16a34a' : '#71717a' }};">
                                    {{ $isOn ? 'On' : 'Off' }}
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>

                    <div style="padding:14px 24px;background:#fafafa;border-top:1px solid #e4e4e7;display:flex;justify-content:flex-end;">
                        <button type="submit" style="background:#18181b;color:white;padding:9px 20px;border-radius:6px;font-weight:600;font-size:0.875rem;border:none;cursor:pointer;">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>
    </div>

</div>

@push('scripts')
<script src="{{ asset('js/settings-notifications.js') }}"></script>
@endpush
@endsection
