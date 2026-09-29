@extends('layouts.app')
@section('title', 'Announcements')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/announcements.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Announcements</h1>
            <p class="page-subtitle">Company updates and team notices</p>
        </div>
        <div class="page-header-right" style="display:flex;gap:8px">
            @if($announcements->where('is_read', false)->count() > 0)
            <form method="POST" action="{{ route('announcements.read-all') }}">
                @csrf
                <button type="submit" class="btn-secondary">Mark all read</button>
            </form>
            @endif
            @if($canPost)
            <button onclick="togglePostForm()" class="btn-primary">+ New Announcement</button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">✅ {{ session('success') }}</div>
    @endif

    {{-- POST FORM --}}
    @if($canPost)
    <div id="post-form" class="ann-post-card" style="display:none">
        <div class="ann-post-title">📢 New Announcement</div>
        <form method="POST" action="{{ route('announcements.store') }}">
            @csrf

            <div class="ann-form-row">
                <div class="form-group" style="flex:2">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control"
                        placeholder="Announcement title..." required maxlength="200"
                        value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control">
                        <option value="normal" {{ old('priority') === 'urgent' ? '' : 'selected' }}>Normal</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>🚨 Urgent</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-md">
                <label class="form-label">Message *</label>
                <textarea name="message" class="form-control" rows="4"
                    placeholder="Write your announcement..." required
                    maxlength="2000">{{ old('message') }}</textarea>
            </div>

            <div class="ann-form-row">
                @if(!auth()->user()->hasRole('team_lead') || auth()->user()->hasAnyRole(['admin','owner','ceo']))
                <div class="form-group">
                    <label class="form-label">Audience</label>
                    <select name="audience" class="form-control" id="audience-select"
                        onchange="toggleAudienceFields()">
                        <option value="org">🌐 Everyone in org</option>
                        <option value="department">🏢 Specific department</option>
                        <option value="team">👥 Specific team</option>
                    </select>
                </div>
                @endif

                <div class="form-group" id="dept-select" style="display:none">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select department...</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" id="team-select" style="display:none">
                    <label class="form-label">Team</label>
                    <select name="team_id" class="form-control">
                        <option value="">Select team...</option>
                        @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Expires (optional)</label>
                    <input type="date" name="expires_at" class="form-control"
                        min="{{ now()->addDay()->format('Y-m-d') }}">
                </div>

                @if(auth()->user()->hasAnyRole(['admin','owner','ceo']))
                <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
                    <label class="ann-pin-label">
                        <input type="checkbox" name="is_pinned" value="1">
                        📌 Pin to top
                    </label>
                </div>
                @endif
            </div>

            <div style="display:flex;gap:8px">
                <button type="submit" class="btn-primary">Post Announcement</button>
                <button type="button" onclick="togglePostForm()" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- ANNOUNCEMENTS LIST --}}
    @if($announcements->isEmpty())
    <div class="fv-empty">
        <div style="font-size:48px;margin-bottom:12px">📢</div>
        <div class="fv-empty-title">No announcements yet</div>
        <div class="fv-empty-desc">
            @if($canPost)
                Post your first announcement above.
            @else
                Your managers will post announcements here.
            @endif
        </div>
    </div>
    @else
    <div class="ann-list">
        @foreach($announcements as $ann)
        <div class="ann-card {{ $ann['is_urgent'] ? 'ann-urgent' : '' }} {{ $ann['is_pinned'] ? 'ann-pinned' : '' }} {{ !$ann['is_read'] ? 'ann-unread' : '' }}"
            id="ann-{{ $ann['id'] }}" onclick="markRead({{ $ann['id'] }})">

            <div class="ann-card-header">
                <div class="ann-card-left">
                    @if($ann['is_pinned'])<span class="ann-pin-icon" title="Pinned">📌</span>@endif
                    @if($ann['is_urgent'])<span class="ann-urgent-badge">🚨 Urgent</span>@endif
                    @if(!$ann['is_read'])<span class="ann-unread-dot"></span>@endif
                    <h3 class="ann-title">{{ $ann['title'] }}</h3>
                </div>
                <div class="ann-card-actions">
                    <span class="ann-audience-badge">{{ $ann['audience_label'] }}</span>
                    @if($ann['can_pin'])
                    <form method="POST" action="{{ route('announcements.pin', $ann['id']) }}"
                        style="display:inline" onclick="event.stopPropagation()">
                        @csrf
                        <button type="submit" class="ann-action-btn"
                            title="{{ $ann['is_pinned'] ? 'Unpin' : 'Pin' }}">
                            {{ $ann['is_pinned'] ? '📌' : '📍' }}
                        </button>
                    </form>
                    @endif
                    @if($ann['can_delete'])
                    <form method="POST" action="{{ route('announcements.destroy', $ann['id']) }}"
                        style="display:inline" onclick="event.stopPropagation()"
                        onsubmit="return confirm('Delete this announcement?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ann-action-btn ann-delete-btn" title="Delete">🗑️</button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="ann-message">{{ $ann['message'] }}</div>

            <div class="ann-card-footer">
                <div class="ann-author">
                    <div class="ann-author-avatar">{{ $ann['avatar'] }}</div>
                    <div>
                        <span class="ann-author-name">{{ $ann['author_name'] }}</span>
                        <span class="ann-author-role">{{ ucfirst($ann['author_role']) }}</span>
                    </div>
                </div>
                <div class="ann-meta">
                    @if($ann['expires_at'])
                    <span class="ann-expires">Expires {{ $ann['expires_at'] }}</span>
                    @endif
                    <span class="ann-time" title="{{ $ann['posted_at_full'] }}">{{ $ann['posted_at'] }}</span>
                    @if($ann['is_read'])<span class="ann-read-badge">✓ Read</span>@endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function togglePostForm() {
    var form = document.getElementById('post-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

function toggleAudienceFields() {
    var audience = document.getElementById('audience-select');
    if (!audience) return;
    var val = audience.value;
    document.getElementById('dept-select').style.display = val === 'department' ? 'block' : 'none';
    document.getElementById('team-select').style.display = val === 'team' ? 'block' : 'none';
}

function markRead(id) {
    var card = document.getElementById('ann-' + id);
    if (!card) return;
    card.classList.remove('ann-unread');
    var dot = card.querySelector('.ann-unread-dot');
    if (dot) dot.remove();
    var meta = card.querySelector('.ann-meta');
    if (meta && !card.querySelector('.ann-read-badge')) {
        var badge = document.createElement('span');
        badge.className = 'ann-read-badge';
        badge.textContent = '✓ Read';
        meta.appendChild(badge);
    }
    fetch('/announcements/' + id + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    });
}
</script>
@endpush
