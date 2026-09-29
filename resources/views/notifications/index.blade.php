@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Your activity feed and alerts</p>
    </div>
    <div class="page-header-right">
        @if($unreadCount > 0)
        <span class="notif-unread-pill">{{ $unreadCount }} unread</span>
        @endif
        <button class="notif-mark-all-btn" data-action="mark-all-read">
            Mark all read
        </button>
    </div>
</div>

    {{-- Filter tabs --}}
    <div class="notif-tabs-bar">
        @php $tab = request('tab', 'all'); @endphp
        @foreach([['all','All'],['unread','Unread'],['critical','Critical'],['dismissed','Dismissed']] as [$val,$label])
        <a href="?tab={{ $val }}" class="notif-tab-link {{ $tab === $val ? 'active' : '' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Notifications list --}}
    @php
        use App\Models\AgentNotification;
        $userId = auth()->id();
        $query  = AgentNotification::forUser($userId)->orderBy('created_at', 'desc');
        if ($tab === 'unread')    $query = $query->unread()->active();
        elseif ($tab === 'critical') $query = $query->critical()->active();
        elseif ($tab === 'dismissed') $query = AgentNotification::forUser($userId)->where('is_dismissed', true)->orderBy('created_at','desc');
        else $query = $query->active();
        $allNotifs = $query->take(100)->get();
        $grouped = $allNotifs->groupBy(function($n) {
            $date = $n->created_at->toDateString();
            if ($date === today()->toDateString()) return 'Today';
            if ($date === today()->subDay()->toDateString()) return 'Yesterday';
            if ($n->created_at->gte(now()->startOfWeek())) return 'This Week';
            return $n->created_at->format('M j, Y');
        });
    @endphp

    <div id="notifications-list">
    @if($allNotifs->isEmpty())
    <div class="notif-empty-state">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="notif-empty-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        <div class="notif-empty-title">
            @if($tab === 'unread') No unread notifications
            @elseif($tab === 'critical') No critical notifications
            @elseif($tab === 'dismissed') No dismissed notifications
            @else No notifications yet
            @endif
        </div>
        <div class="notif-empty-desc">
            @if($tab === 'all') The AI agent is watching your team and will alert you here when action is needed.
            @else Check the All tab to see your full notification history.
            @endif
        </div>
    </div>
    @else
    <div class="notif-date-group-list" id="notif-page-list">
        @foreach($grouped as $dateLabel => $items)
        <div>
            <div class="notif-date-label">{{ $dateLabel }}</div>
            <div class="notif-cards">
                @foreach($items as $notif)
                <div id="notif-card-{{ $notif->id }}" class="notif-card {{ $notif->is_read ? 'read' : '' }}">
                    <div class="notif-card-body">
                        <div class="notif-priority-dot notif-dot-{{ $notif->priority ?? 'low' }}"></div>
                        <div class="notif-card-content">
                            <div class="notif-card-header">
                                <div class="notif-title {{ $notif->is_read ? 'read' : '' }}">
                                    {{ $notif->title }}
                                </div>
                                <div class="notif-meta">
                                    @if(!$notif->is_read)
                                    <span class="notif-unread-dot"></span>
                                    @endif
                                    <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="notif-message">{{ $notif->message }}</div>
                            <div class="notif-footer">
                                <div class="notif-footer-left">
                                    @if($notif->action_url)
                                    <a href="{{ $notif->action_url }}"
                                        class="notif-action-btn"
                                        data-action="mark-read"
                                        data-notif-id="{{ $notif->id }}">
                                        {{ $notif->action_label ?? 'View' }}
                                    </a>
                                    @endif
                                    @if(!$notif->is_read)
                                    <button class="notif-read-btn"
                                        data-action="mark-read"
                                        data-notif-id="{{ $notif->id }}">
                                        Mark read
                                    </button>
                                    @endif
                                </div>
                                @if(!$notif->is_dismissed)
                                <button class="notif-dismiss-btn"
                                    data-action="dismiss"
                                    data-notif-id="{{ $notif->id }}">
                                    Dismiss
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif
    </div>

    <div id="notifications-pagination" class="pagination-wrapper"></div>

</div>

@push('scripts')
<script>window.NOTIF_CSRF='{{ csrf_token() }}';</script>
<script src="{{ asset('js/notifications-page.js') }}"></script>
@endpush
@endsection
